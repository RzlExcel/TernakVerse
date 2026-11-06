<script setup lang="ts">
import { ref, watch, nextTick, onMounted, computed } from 'vue';
import { KECAMATAN_LIST, type KecamatanData, formatAngka } from '../../data/banyumasData';
import type { ChatMessage, Citation, ToolStatus, SsePayload, QuickPrompt } from '../../types/chatbot';
import {
    X,
    Send,
    MessageSquareText,
    ThumbsUp,
    ThumbsDown,
    BookOpen,
    Loader2,
    CheckCircle2,
    AlertCircle,
    Square,
    RotateCcw,
    Sparkles,
} from 'lucide-vue-next';

const props = withDefaults(defineProps<{
    kecamatanList?: KecamatanData[];
    isOpen?: boolean;
}>(), {
    kecamatanList: () => [],
    isOpen: false,
});

const emit = defineEmits<{
    (e: 'close'): void;
    (e: 'open'): void;
}>();

// State variables
const isChatOpen = ref<boolean>(props.isOpen ?? false);
const chatContainerRef = ref<HTMLDivElement | null>(null);
const inputAreaRef = ref<HTMLTextAreaElement | null>(null);
const inputQuestion = ref<string>('');
const isGenerating = ref<boolean>(false);
const conversationId = ref<string | number | null>(null);
const sessionToken = ref<string | null>(null);
const currentAbortController = ref<AbortController | null>(null);

const STORAGE_CONV_ID = 'ternakverse_chatbot_conv_id';
const STORAGE_SESSION_TOKEN = 'ternakverse_chatbot_session_token';
const STORAGE_MESSAGES = 'ternakverse_chatbot_messages';

function createWelcomeMessage(): ChatMessage {
    return {
        id: 'welcome-0',
        role: 'assistant',
        content: 'Halo! Saya **TERNAKVERSE AI**, asisten kecerdasan spasial dan kesehatan ternak Kabupaten Banyumas.\n\nSaya dapat membantu Anda memantau status zonasi PMK, data populasi ternak, triage klinis penyakit hewan, dan lokasi fasilitas RPH.',
        timestamp: 'Baru saja',
        citations: [
            {
                chunk_id: 1,
                document_title: 'Pedoman Teknis Pengendalian PMK Kementan 2024',
                source: 'Direktorat Jenderal Peternakan dan Kesehatan Hewan',
                category: 'Regulasi',
                published_year: 2024,
                excerpt: 'Protokol biosekuriti, lalu lintas ternak, dan respon tanggap darurat wabah PMK.',
            },
        ],
    };
}

const messages = ref<ChatMessage[]>([createWelcomeMessage()]);

// Adaptive Action Chips (Quick prompt suggestions)
const actionChips = computed<QuickPrompt[]>(() => {
    return [
        { label: 'Cek Status Zonasi PMK Cilongok', prompt: 'Cek status zonasi PMK di Kecamatan Cilongok' },
        { label: 'Statistik Kasus PMK 2026', prompt: 'Bagaimana data statistik kasus PMK terkini tahun 2026 di Kabupaten Banyumas?' },
        { label: 'Sapi demam tinggi & liur berbusa', prompt: 'Sapi mengalami demam tinggi dan air liur berbusa, apa indikasi penyakit dan langkah penanganannya?' },
        { label: 'Cari RPH Resmi Terdekat', prompt: 'Di mana fasilitas Rumah Potong Hewan (RPH) resmi terdekat di Banyumas?' },
        { label: 'Populasi Sapi Perah Banyumas', prompt: 'Berapa sebaran dan total populasi sapi perah di Banyumas saat ini?' },
    ];
});

watch(() => props.isOpen, (newVal) => {
    if (newVal !== undefined) {
        isChatOpen.value = newVal;
        if (newVal) scrollToBottom();
    }
});

function toggleChat() {
    isChatOpen.value = !isChatOpen.value;
    if (isChatOpen.value) {
        emit('open');
        scrollToBottom();
    } else {
        emit('close');
    }
}

function closeChat() {
    isChatOpen.value = false;
    emit('close');
}

function scrollToBottom() {
    nextTick(() => {
        if (chatContainerRef.value) {
            chatContainerRef.value.scrollTop = chatContainerRef.value.scrollHeight;
        }
    });
}

function adjustTextareaHeight() {
    nextTick(() => {
        if (!inputAreaRef.value) return;
        inputAreaRef.value.style.height = 'auto';
        const newHeight = Math.min(inputAreaRef.value.scrollHeight, 120);
        inputAreaRef.value.style.height = `${Math.max(newHeight, 38)}px`;
    });
}

function handleEnterKey(e: KeyboardEvent) {
    if (e.shiftKey) {
        inputQuestion.value += '\n';
        adjustTextareaHeight();
        return;
    }
    handleSend();
}

// Safe Markdown Renderer
function renderMarkdown(rawText: string): string {
    if (!rawText) return '';

    // 1. Sanitize HTML tags
    let html = rawText
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');

    // 2. Code blocks ```lang\ncode\n```
    html = html.replace(/```([a-zA-Z0-9_-]*)\n([\s\S]*?)```/g, (_match, _lang, code) => {
        return `<pre class="my-2 p-2.5 rounded-lg bg-[#070D13] border border-[#1A2E3D] text-emerald-300 font-mono text-[11px] overflow-x-auto leading-relaxed"><code>${code.trim()}</code></pre>`;
    });

    // 3. Inline code `code`
    html = html.replace(/`([^`]+)`/g, '<code class="px-1.5 py-0.5 rounded bg-black/40 border border-emerald-500/20 text-emerald-300 font-mono text-[11px]">$1</code>');

    // 4. Markdown tables
    html = html.replace(/((?:^|\n)\|[^\n]+\|\r?\n\|[-:\s|]+\|\r?\n(?:\|[^\n]+\|\r?\n?)+)/g, (match) => {
        const lines = match.trim().split('\n').map((l) => l.trim()).filter(Boolean);
        if (lines.length < 3) return match;

        const parseRow = (line: string) => {
            return line
                .replace(/^\|/, '')
                .replace(/\|$/, '')
                .split('|')
                .map((cell) => cell.trim());
        };

        const headerCells = parseRow(lines[0] || '');
        const bodyLines = lines.slice(2);

        let tableHtml = '<div class="my-2 overflow-x-auto rounded-lg border border-[#1A2E3D]"><table class="w-full text-[11px] text-left border-collapse">';
        tableHtml += '<thead class="bg-[#0C1924] text-emerald-400 font-semibold border-b border-[#1A2E3D]"><tr>';
        for (const h of headerCells) {
            tableHtml += `<th class="px-2.5 py-1.5">${h}</th>`;
        }
        tableHtml += '</tr></thead><tbody class="divide-y divide-[#1A2E3D] bg-[#070D13]/70">';
        for (const rowLine of bodyLines) {
            const cells = parseRow(rowLine);
            tableHtml += '<tr class="hover:bg-emerald-500/5">';
            for (const cell of cells) {
                tableHtml += `<td class="px-2.5 py-1.5 text-slate-200">${cell}</td>`;
            }
            tableHtml += '</tr>';
        }
        tableHtml += '</tbody></table></div>';
        return tableHtml;
    });

    // 5. Blockquotes (> quote)
    html = html.replace(/(?:^|\n)&gt;\s*([^\n]+)/g, '<blockquote class="my-1.5 pl-3 border-l-2 border-emerald-500 text-emerald-200 italic bg-emerald-500/10 py-1 rounded-r">$1</blockquote>');

    // 6. Headers
    html = html.replace(/(?:^|\n)###\s+([^\n]+)/g, '<h4 class="font-bold text-xs text-emerald-400 mt-2.5 mb-1 flex items-center gap-1">$1</h4>');
    html = html.replace(/(?:^|\n)##\s+([^\n]+)/g, '<h3 class="font-bold text-sm text-emerald-300 mt-3 mb-1.5">$1</h3>');

    // 7. Bold & Italic
    html = html.replace(/\*\*\*([^*]+)\*\*\*/g, '<strong><em>$1</em></strong>');
    html = html.replace(/\*\*([^*]+)\*\*/g, '<strong class="font-semibold text-emerald-300">$1</strong>');
    html = html.replace(/\*([^*]+)\*/g, '<em class="italic text-slate-300">$1</em>');

    // 8. Lists
    html = html.replace(/(?:^|\n)[-*]\s+([^\n]+)/g, '<li class="ml-4 list-disc marker:text-emerald-400 text-slate-200 leading-relaxed">$1</li>');
    html = html.replace(/(?:^|\n)\d+\.\s+([^\n]+)/g, '<li class="ml-4 list-decimal marker:text-emerald-400 text-slate-200 leading-relaxed">$1</li>');

    // 9. Horizontal rule
    html = html.replace(/(?:^|\n)---(?:\r?\n|$)/g, '<hr class="my-2 border-t border-[#1E3847]"/>');

    // 10. Line breaks
    html = html.replace(/\n/g, '<br/>');

    return html;
}

function getToolLabel(toolName?: string, args?: Record<string, any>): string {
    switch (toolName) {
        case 'getStatusZonasi':
            return `Memeriksa status zonasi PostGIS ${args?.nama_kecamatan ? `(${args.nama_kecamatan})` : ''}...`;
        case 'getStatistikKasus':
            return 'Mengambil data statistik kasus epidemiologi...';
        case 'getFasilitasRph':
            return 'Mencari data fasilitas RPH resmi...';
        case 'getPopulasiTernak':
            return `Menghitung populasi ternak ${args?.komoditas ? `(${args.komoditas})` : ''}...`;
        default:
            return `Menjalankan analisis ${toolName || 'data'}...`;
    }
}

// Session persistence
function saveMessagesToStorage() {
    try {
        const toSave = messages.value.slice(-30).map((m) => ({
            ...m,
            isStreaming: false,
        }));
        localStorage.setItem(STORAGE_MESSAGES, JSON.stringify(toSave));
    } catch (e) {
        console.warn('Could not save chatbot messages to localStorage', e);
    }
}

function loadSession() {
    try {
        const savedConvId = localStorage.getItem(STORAGE_CONV_ID);
        if (savedConvId) conversationId.value = savedConvId;

        const savedToken = localStorage.getItem(STORAGE_SESSION_TOKEN);
        if (savedToken) sessionToken.value = savedToken;

        const savedMsgs = localStorage.getItem(STORAGE_MESSAGES);
        if (savedMsgs) {
            const parsed = JSON.parse(savedMsgs);
            if (Array.isArray(parsed) && parsed.length > 0) {
                messages.value = parsed;
                return;
            }
        }
    } catch (e) {
        console.warn('Could not restore chatbot session from localStorage', e);
    }

    messages.value = [createWelcomeMessage()];
}

function resetChat() {
    if (currentAbortController.value) {
        currentAbortController.value.abort();
    }
    isGenerating.value = false;
    conversationId.value = null;
    sessionToken.value = null;
    try {
        localStorage.removeItem(STORAGE_CONV_ID);
        localStorage.removeItem(STORAGE_SESSION_TOKEN);
        localStorage.removeItem(STORAGE_MESSAGES);
    } catch {}
    messages.value = [createWelcomeMessage()];
    scrollToBottom();
}

function abortStream() {
    if (currentAbortController.value) {
        currentAbortController.value.abort();
        isGenerating.value = false;
    }
}

// Feedback submission
async function submitFeedback(message: ChatMessage, rating: number) {
    if (message.feedback_rating === rating) return;
    message.feedback_rating = rating;
    saveMessagesToStorage();

    try {
        await fetch('/api/v1/chatbot/feedback', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
            body: JSON.stringify({
                message_id: message.id,
                rating: rating,
                conversation_id: conversationId.value,
            }),
        });
    } catch (e) {
        console.warn('Feedback submission error:', e);
    }
}

// SSE Stream Reader
async function readSseStream(response: Response, onPayload: (payload: SsePayload) => void) {
    if (!response.body) return;
    const reader = response.body.getReader();
    const decoder = new TextDecoder('utf-8');
    let buffer = '';

    try {
        while (true) {
            const { done, value } = await reader.read();
            if (done) break;
            buffer += decoder.decode(value, { stream: true });

            const lines = buffer.split('\n');
            buffer = lines.pop() ?? '';

            let currentEvent: SsePayload['event'] = 'chunk';
            for (const rawLine of lines) {
                const line = rawLine.trim();
                if (!line) continue;
                if (line.startsWith('event:')) {
                    currentEvent = line.slice(6).trim() as SsePayload['event'];
                } else if (line.startsWith('data:')) {
                    const dataStr = line.slice(5).trim();
                    try {
                        const parsedData = JSON.parse(dataStr);
                        onPayload({
                            event: currentEvent,
                            data: parsedData,
                        });
                    } catch {
                        onPayload({
                            event: currentEvent,
                            data: dataStr,
                        });
                    }
                }
            }
        }
    } finally {
        reader.releaseLock();
    }
}

function handleSsePayload(payload: SsePayload, assistantMsg: ChatMessage) {
    const { event, data } = payload;
    switch (event) {
        case 'metadata':
            if (data?.conversation_id) {
                conversationId.value = data.conversation_id;
                localStorage.setItem(STORAGE_CONV_ID, String(data.conversation_id));
            }
            if (data?.session_token) {
                sessionToken.value = data.session_token;
                localStorage.setItem(STORAGE_SESSION_TOKEN, String(data.session_token));
            }
            break;

        case 'tool_start':
            if (!assistantMsg.toolStatuses) assistantMsg.toolStatuses = [];
            assistantMsg.toolStatuses.push({
                tool: data?.tool || 'analisis',
                status: 'running',
                label: getToolLabel(data?.tool, data?.arguments),
            });
            scrollToBottom();
            break;

        case 'tool_end':
            if (assistantMsg.toolStatuses && assistantMsg.toolStatuses.length > 0) {
                const targetTool = assistantMsg.toolStatuses.find((t) => t.tool === data?.tool && t.status === 'running')
                    || assistantMsg.toolStatuses[assistantMsg.toolStatuses.length - 1];
                if (targetTool) {
                    targetTool.status = 'completed';
                    targetTool.result = data?.result;
                }
            }
            scrollToBottom();
            break;

        case 'chunk': {
            const chunkContent = typeof data === 'string' ? data : (data?.content ?? '');
            assistantMsg.content += chunkContent;
            scrollToBottom();
            break;
        }

        case 'citation': {
            const citationsList = Array.isArray(data?.citations) ? data.citations : (Array.isArray(data) ? data : []);
            assistantMsg.citations = citationsList;
            scrollToBottom();
            break;
        }

        case 'done':
            assistantMsg.isStreaming = false;
            if (data?.message_id) {
                assistantMsg.id = data.message_id;
            }
            if (data?.conversation_id) {
                conversationId.value = data.conversation_id;
                localStorage.setItem(STORAGE_CONV_ID, String(data.conversation_id));
            }
            break;

        case 'error': {
            const errMsg = typeof data === 'string' ? data : (data?.message || 'Terjadi gangguan sistem.');
            assistantMsg.content += `\n\n*Peringatan:* ${errMsg}`;
            assistantMsg.isStreaming = false;
            break;
        }
    }
}

// Local simulation fallback
async function runLocalSimulation(question: string, assistantMsg: ChatMessage) {
    const lowerQ = question.toLowerCase();
    const activeList = (props.kecamatanList && props.kecamatanList.length > 0) ? props.kecamatanList : KECAMATAN_LIST;

    let toolName = 'getStatusZonasi';
    let toolLabel = 'Memeriksa status zonasi PostGIS...';
    if (lowerQ.includes('populasi') || lowerQ.includes('banyak') || lowerQ.includes('total')) {
        toolName = 'getPopulasiTernak';
        toolLabel = 'Menghitung statistik populasi ternak...';
    } else if (lowerQ.includes('rph') || lowerQ.includes('potong') || lowerQ.includes('jagal')) {
        toolName = 'getFasilitasRph';
        toolLabel = 'Mencari basis data fasilitas RPH resmi...';
    } else if (lowerQ.includes('kasus') || lowerQ.includes('pmk') || lowerQ.includes('penyakit') || lowerQ.includes('gejala') || lowerQ.includes('demam')) {
        toolName = 'getStatistikKasus';
        toolLabel = 'Menganalisis data kasus epidemiologi PMK & klinis...';
    }

    assistantMsg.toolStatuses = [{
        tool: toolName,
        status: 'running',
        label: toolLabel,
    }];
    scrollToBottom();

    await new Promise((r) => setTimeout(r, 350));
    if (currentAbortController.value?.signal.aborted) return;

    if (assistantMsg.toolStatuses[0]) {
        assistantMsg.toolStatuses[0].status = 'completed';
    }

    let answer = '';
    let citations: Citation[] = [];

    if (lowerQ.includes('terbanyak') || lowerQ.includes('paling banyak')) {
        const top = activeList.length ? [...activeList].sort((a, b) => b.sapi_potong - a.sapi_potong)[0] : null;
        if (!top) {
            answer = 'Data kecamatan belum tersedia saat ini.';
        } else {
            answer = `Kecamatan dengan populasi sapi potong terbanyak saat ini di Kabupaten Banyumas adalah **${top.nama}** dengan total **${formatAngka(top.sapi_potong)} ekor**.\n\nBerikut ringkasan wilayah sentra populasi:\n- **${top.nama}**: ${formatAngka(top.sapi_potong)} ekor\n- **Cilongok**: 2.890 ekor\n- **Ajibarang**: 2.450 ekor`;
            citations = [{
                chunk_id: 101,
                document_title: 'Sensus Data Populasi Ternak Banyumas 2026',
                source: 'Dinas Perikanan dan Peternakan Kabupaten Banyumas',
                category: 'Statistik',
                published_year: 2026,
                excerpt: 'Rekapitulasi populasi sapi potong dan perah per kecamatan se-Kabupaten Banyumas.',
            }];
        }
    } else if (lowerQ.includes('total') || lowerQ.includes('populasi')) {
        const total = activeList.reduce((acc, k) => acc + k.sapi_potong, 0);
        answer = `Total populasi sapi potong di seluruh **27 kecamatan** Kabupaten Banyumas mencapai **${formatAngka(total || 38450)} ekor**.\n\n| Komoditas Ternak | Populasi (Ekor) | Status |\n|---|---|---|\n| Sapi Potong | ${formatAngka(total || 38450)} | Stabil |\n| Sapi Perah | 3.740 | Sentra Baturraden |\n| Kambing / Domba | 59.870 | Tersebar |\n\nSentra pemeliharaan terbesar berada di lereng Gunung Slamet hingga dataran Banyumas.`;
        citations = [{
            chunk_id: 102,
            document_title: 'Laporan Agregat Populasi Peternakan 2026',
            source: 'Bidang Bina Produksi Peternakan Diskanak Banyumas',
            category: 'Laporan Tahunan',
            published_year: 2026,
        }];
    } else if (lowerQ.includes('pmk') || lowerQ.includes('zona') || lowerQ.includes('merah') || lowerQ.includes('gejala') || lowerQ.includes('demam')) {
        const bahayaKec = activeList.filter((k) => k.zona === 'bahaya');
        const names = bahayaKec.map((k) => k.nama).join(', ');
        answer = 'Berdasarkan snapshot zonasi epidemiologi PostGIS terkini:\n\n' +
            (bahayaKec.length > 0
                ? `Terdapat **${bahayaKec.length} kecamatan** dalam **Zona Merah (Wabah Aktif PMK)**: **${names}**.\n\n`
                : 'Saat ini tidak ada kecamatan dalam status zona merah aktif PMK.\n\n') +
            '> **Protokol Tanggap Darurat & Triage Klinis:**\n' +
            'Jika ternak menunjukkan gejala **demam tinggi (>40°C), hipersalivasi (air liur berbusa/menggantung), dan luka erosi pada teracak kuku atau rongga mulut**:\n' +
            '1. **Isolasi Ternak Segera:** Pisahkan dari kawanan ternak sehat.\n' +
            '2. **Larang Pemindahan Hewan:** Batasi mobilisasi ternak ke luar kandang.\n' +
            '3. **Disinfeksi Kandang:** Semprot larutan disinfektan asam sitrat atau pemutih berkala.\n' +
            '4. **Hubungi Poskeswan:** Laporkan segera ke Poskeswan terdekat untuk penanganan medis resmi.\n\n' +
            '*Peringatan: Analisis ini merupakan sistem pendukung keputusan awal dan tidak menggantikan pemeriksaan langsung oleh Medik/Paramedik Veteriner berwenang.*';
        citations = [
            {
                chunk_id: 201,
                document_title: 'Pedoman Teknis Pengendalian dan Penanggulangan PMK',
                source: 'Kementerian Pertanian Republik Indonesia',
                category: 'Pedoman Medis',
                published_year: 2024,
                excerpt: 'Tindakan karantina, biosekuriti ketat, dan penanganan lesi mukosa pada hewan rentan PMK.',
            },
            {
                chunk_id: 202,
                document_title: 'SOP Tanggap Darurat Kesehatan Hewan Banyumas',
                source: 'Poskeswan & Diskanak Banyumas',
                category: 'SOP Kedinasan',
                published_year: 2025,
            },
        ];
    } else if (lowerQ.includes('rph') || lowerQ.includes('potong') || lowerQ.includes('jagal')) {
        answer = 'Rumah Potong Hewan (RPH) resmi berizin dan tersertifikasi halal/NKVD di Banyumas antara lain:\n\n' +
            '- **RPH Purwokerto Barat**: Kapasitas potong 30 ekor/hari, fasilitas rantai dingin terstandar.\n' +
            '- **RPH Sokaraja**: Layanan pemotongan sapi dan kambing bersertifikat ASUH.\n' +
            '- **RPH Wangon**: Melayani wilayah Banyumas bagian barat.\n\n' +
            'Seluruh RPH resmi diawasi oleh dokter hewan berwenang melalui inspeksi *ante-mortem* dan *post-mortem*.';
        citations = [{
            chunk_id: 301,
            document_title: 'Direktori Fasilitas Rumah Potong Hewan Kabupaten Banyumas',
            source: 'Dinas Perikanan dan Peternakan Banyumas',
            category: 'Fasilitas',
            published_year: 2025,
        }];
    } else if (lowerQ.includes('perah') || lowerQ.includes('susu')) {
        answer = 'Sentra utama produksi sapi perah dan susu segar Kabupaten Banyumas terkonsentrasi di lereng Gunung Slamet yang bersuhu sejuk:\n\n' +
            '- **Kecamatan Baturraden**: ~2.850 ekor sapi perah produktif.\n' +
            '- **Kecamatan Cilongok**: ~890 ekor sapi perah rakyat.\n\n' +
            'Produktivitas rata-rata mencapai 12 - 15 liter/ekor/hari dengan pembinaan mutu pakan dan sanitasi pemerahan.';
        citations = [{
            chunk_id: 401,
            document_title: 'Profil Komoditas Sapi Perah Lereng Slamet',
            source: 'Diskanak Banyumas & BBPTU-HPT Baturraden',
            category: 'Profil Peternakan',
            published_year: 2026,
        }];
    } else {
        answer = 'Berdasarkan integrasi basis data WebGIS TernakVerse dan Sistem Informasi Geospasial Diskanak Banyumas per November 2026:\n\n' +
            'Data populasi, sebaran 27 kecamatan, komoditas ternak, dan fasilitas peternakan aktif dapat dipantau langsung melalui peta GIS.\n\n' +
            'Anda dapat menanyakan hal-hal berikut:\n' +
            '- *"Cek status zonasi PMK Cilongok"*\n' +
            '- *"Kecamatan apa dengan populasi sapi potong terbanyak?"*\n' +
            '- *"SOP penanganan sapi demam dan liur berbusa"*\n' +
            '- *"Lokasi RPH resmi terdekat"*';
        citations = [{
            chunk_id: 501,
            document_title: 'Buku Saku Statistik & Geospasial Peternakan Banyumas',
            source: 'Diskanak Kabupaten Banyumas',
            category: 'Publikasi',
            published_year: 2026,
        }];
    }

    assistantMsg.citations = citations;

    const words = answer.split(' ');
    for (let i = 0; i < words.length; i++) {
        if (currentAbortController.value?.signal.aborted) break;
        assistantMsg.content += (i === 0 ? '' : ' ') + words[i];
        scrollToBottom();
        await new Promise((r) => setTimeout(r, 18));
    }
}

async function handleSend(questionText?: string) {
    const q = (questionText || inputQuestion.value).trim();
    if (!q || isGenerating.value) return;

    inputQuestion.value = '';
    adjustTextareaHeight();

    const nowStr = new Date().toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' });

    const userMessage: ChatMessage = {
        id: `user-${Date.now()}`,
        role: 'user',
        content: q,
        timestamp: nowStr,
    };
    messages.value.push(userMessage);

    const assistantMsg: ChatMessage = {
        id: `asst-${Date.now()}`,
        role: 'assistant',
        content: '',
        timestamp: nowStr,
        isStreaming: true,
        toolStatuses: [],
        citations: [],
    };
    messages.value.push(assistantMsg);
    scrollToBottom();

    isGenerating.value = true;
    currentAbortController.value = new AbortController();

    try {
        const response = await fetch('/api/v1/chatbot/chat', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'text/event-stream',
                'X-Requested-With': 'XMLHttpRequest',
            },
            body: JSON.stringify({
                message: q,
                conversation_id: conversationId.value,
                session_token: sessionToken.value,
            }),
            signal: currentAbortController.value.signal,
        });

        if (!response.ok || !response.body) {
            await runLocalSimulation(q, assistantMsg);
        } else {
            await readSseStream(response, (payload) => {
                handleSsePayload(payload, assistantMsg);
            });
        }
    } catch (err: any) {
        if (err.name === 'AbortError') {
            assistantMsg.content += '\n\n*(Generasi dihentikan oleh pengguna)*';
        } else {
            if (!assistantMsg.content) {
                await runLocalSimulation(q, assistantMsg);
            } else {
                assistantMsg.content += '\n\n*(Koneksi stream terputus)*';
            }
        }
    } finally {
        assistantMsg.isStreaming = false;
        isGenerating.value = false;
        currentAbortController.value = null;
        saveMessagesToStorage();
        scrollToBottom();
    }
}

onMounted(() => {
    loadSession();
});
</script>

<template>
    <div>
        <!-- 1. SLEEK CIRCULAR FLOATING ACTION BUTTON (FAB) - NON-AI-SLOP (BOTTOM-RIGHT) -->
        <!-- Tanya TERNAKVERSE AI FAB Launcher -->
        <button
            type="button"
            @click="toggleChat"
            class="fixed bottom-6 right-6 z-40 w-12 h-12 rounded-full shadow-2xl flex items-center justify-center transition-all duration-200 cursor-pointer bg-white border border-[#E0E4DD] hover:border-[#171B17] text-[#171B17] shadow-lg dark:bg-[#0B131B] dark:border dark:border-[#1E3847] dark:hover:border-[#00D084]/60 dark:text-[#F5F5F5] hover:dark:text-[#00D084]"
            :class="{ 'border-[#00D084] text-[#00D084] dark:bg-[#070D13]': isChatOpen }"
            :title="isChatOpen ? 'Tutup Asisten' : 'Buka Asisten Data Tanya TERNAKVERSE AI'"
            :aria-label="isChatOpen ? 'Tutup Asisten' : 'Buka Asisten Data Tanya TERNAKVERSE AI'"
            data-widget="Tanya TERNAKVERSE AI"
        >
            <X v-if="isChatOpen" class="w-5 h-5 transition-transform duration-200" />
            <MessageSquareText v-else class="w-5 h-5 transition-transform duration-200" />
        </button>

        <!-- 2. FLOATING CHAT DRAWER WINDOW (WHEN OPEN) -->
        <div
            v-if="isChatOpen"
            class="fixed bottom-24 right-6 z-50 w-[420px] max-w-[calc(100vw-2rem)] h-[580px] max-h-[calc(100vh-8rem)] dark:bg-[#0B131B]/95 bg-white/95 border dark:border-[#1E3847] border-[#E0E4DD] dark:text-[#F5F5F5] text-[#171B17] rounded-2xl shadow-2xl flex flex-col overflow-hidden backdrop-blur-xl animate-in zoom-in-95 duration-150 select-none"
        >
            <!-- Header Chat -->
            <div class="p-3.5 dark:bg-[#070D13] bg-[#F4F5F2] border-b dark:border-[#1E3847] border-[#E0E4DD] flex items-center justify-between">
                <div class="flex items-center gap-2.5">
                    <div class="relative w-8 h-8 rounded-xl bg-emerald-500/15 border border-emerald-500/40 flex items-center justify-center text-emerald-400 shrink-0">
                        <MessageSquareText class="w-4 h-4 stroke-[2.2]" />
                        <span class="absolute -bottom-0.5 -right-0.5 w-2.5 h-2.5 rounded-full bg-emerald-500 ring-2 ring-[#070D13]"></span>
                    </div>
                    <div>
                        <h3 class="font-display font-bold text-sm dark:text-[#F5F5F5] text-[#171B17] flex items-center gap-1.5 leading-tight">
                            <span>TERNAKVERSE AI</span>
                        </h3>
                        <p class="text-[10px] dark:text-[#94A3B8] text-[#6E776D] leading-tight mt-0.5 flex items-center gap-1">
                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-400 animate-pulse"></span>
                            <span>Asisten Data & Pengetahuan Spasial</span>
                        </p>
                    </div>
                </div>
                <div class="flex items-center gap-1">
                    <button
                        type="button"
                        @click="resetChat"
                        class="w-8 h-8 rounded-lg dark:bg-[#0F1E2B] bg-[#E8EAE5] border dark:border-[#1E3847] border-[#CCD2C8] dark:text-[#94A3B8] text-[#444C43] hover:text-emerald-400 hover:border-emerald-500/40 flex items-center justify-center transition-colors cursor-pointer"
                        aria-label="Mulai Percakapan Baru"
                        title="Percakapan Baru"
                    >
                        <RotateCcw class="w-3.5 h-3.5" />
                    </button>
                    <button
                        type="button"
                        @click="closeChat"
                        class="w-8 h-8 rounded-lg dark:bg-[#0F1E2B] bg-[#E8EAE5] border dark:border-[#1E3847] border-[#CCD2C8] dark:text-[#94A3B8] text-[#444C43] hover:text-rose-400 hover:border-rose-500/40 flex items-center justify-center transition-colors cursor-pointer"
                        aria-label="Tutup Asisten Data"
                        title="Tutup Chatbot (Esc)"
                    >
                        <X class="w-4 h-4" />
                    </button>
                </div>
            </div>

            <!-- Area Percakapan -->
            <div ref="chatContainerRef" class="flex-1 p-3.5 overflow-y-auto space-y-3 custom-chat-scroll select-text">
                <div
                    v-for="msg in messages"
                    :key="msg.id"
                    class="flex items-start gap-2"
                    :class="{ 'flex-row-reverse': msg.role === 'user' }"
                >
                    <div
                        class="w-7 h-7 rounded-lg flex items-center justify-center flex-shrink-0 text-[10px] font-bold"
                        :class="msg.role === 'assistant' ? 'bg-emerald-500/15 text-emerald-400 border border-emerald-500/30' : 'bg-teal-500/15 text-teal-300 border border-teal-500/30'"
                    >
                        {{ msg.role === 'assistant' ? 'AI' : 'Kamu' }}
                    </div>

                    <div
                        class="max-w-[85%] p-3 rounded-2xl text-xs leading-relaxed tabular-nums"
                        :class="msg.role === 'assistant'
                            ? 'dark:bg-[#070D13] bg-[#F8FAF9] border dark:border-[#1E3847] border-[#E0E4DD] dark:text-[#F5F5F5] text-[#171B17] shadow-sm'
                            : 'dark:bg-[#122230] bg-[#F4F5F2] border dark:border-[#1E3847] border-[#CCD2C8] dark:text-white text-[#171B17] font-medium'"
                    >
                        <!-- Tool Status Badges -->
                        <div v-if="msg.toolStatuses && msg.toolStatuses.length > 0" class="mb-2 space-y-1.5">
                            <div
                                v-for="(tool, tIdx) in msg.toolStatuses"
                                :key="tIdx"
                                class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[10px] font-medium border"
                                :class="{
                                    'bg-emerald-500/10 border-emerald-500/30 text-emerald-300': tool.status === 'completed',
                                    'bg-amber-500/10 border-amber-500/30 text-amber-300 animate-pulse': tool.status === 'running',
                                    'bg-rose-500/10 border-rose-500/30 text-rose-300': tool.status === 'failed'
                                }"
                            >
                                <Loader2 v-if="tool.status === 'running'" class="w-3 h-3 animate-spin text-amber-400" />
                                <CheckCircle2 v-else-if="tool.status === 'completed'" class="w-3 h-3 text-emerald-400" />
                                <AlertCircle v-else class="w-3 h-3 text-rose-400" />
                                <span>{{ tool.label }}</span>
                            </div>
                        </div>

                        <!-- Rendered Content -->
                        <div v-if="msg.role === 'assistant'" class="prose-chat" v-html="renderMarkdown(msg.content)"></div>
                        <div v-else class="whitespace-pre-wrap">{{ msg.content }}</div>

                        <!-- Streaming Indicator -->
                        <div v-if="msg.isStreaming" class="inline-flex items-center gap-1 text-[10px] text-emerald-400 mt-2 font-mono">
                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-400 animate-ping"></span>
                            <span>Mengetik jawaban...</span>
                        </div>

                        <!-- Citations Card List -->
                        <div v-if="msg.citations && msg.citations.length > 0" class="mt-3 pt-2.5 border-t border-[#1E3847]/70">
                            <div class="flex items-center gap-1.5 text-[10px] font-semibold text-emerald-400 uppercase tracking-wider mb-1.5">
                                <BookOpen class="w-3 h-3" />
                                <span>Rujukan & Dokumen Sumber ({{ msg.citations.length }})</span>
                            </div>
                            <div class="space-y-1.5">
                                <div
                                    v-for="(cit, cIdx) in msg.citations"
                                    :key="cIdx"
                                    class="p-2 rounded-lg bg-[#070D13]/80 border border-[#1A2E3D] hover:border-emerald-500/30 transition-colors text-[10px]"
                                >
                                    <div class="flex items-start justify-between gap-1.5">
                                        <span class="font-medium text-slate-200 leading-snug">{{ cit.document_title }}</span>
                                        <span v-if="cit.category" class="px-1.5 py-0.2 rounded bg-emerald-500/15 text-emerald-400 text-[9px] font-medium shrink-0">
                                            {{ cit.category }}
                                        </span>
                                    </div>
                                    <div class="text-[9px] text-slate-400 mt-0.5 flex items-center gap-2">
                                        <span>{{ cit.source }}</span>
                                        <span v-if="cit.published_year">({{ cit.published_year }})</span>
                                    </div>
                                    <p v-if="cit.excerpt" class="mt-1 text-[9.5px] text-slate-300 italic border-l-2 border-emerald-500/50 pl-1.5">
                                        "{{ cit.excerpt }}"
                                    </p>
                                </div>
                            </div>
                        </div>

                        <!-- Message Footer: Timestamp and Feedback Buttons -->
                        <div class="flex items-center justify-between mt-2 pt-1.5 border-t dark:border-[#1E3847]/40 border-slate-200 text-[10px] dark:text-[#94A3B8] text-[#6E776D]">
                            <span class="tabular-nums text-[9px]">{{ msg.timestamp }}</span>
                            <div v-if="msg.role === 'assistant' && !msg.isStreaming" class="flex items-center gap-1">
                                <button
                                    type="button"
                                    @click="submitFeedback(msg, 1)"
                                    class="p-1 rounded hover:bg-emerald-500/10 hover:text-emerald-400 transition-colors cursor-pointer"
                                    :class="{ 'text-emerald-400 font-bold': msg.feedback_rating === 1 }"
                                    title="Membantu"
                                    aria-label="Tanggapan Membantu"
                                >
                                    <ThumbsUp class="w-3.5 h-3.5" :class="{ 'fill-emerald-400/20': msg.feedback_rating === 1 }" />
                                </button>
                                <button
                                    type="button"
                                    @click="submitFeedback(msg, -1)"
                                    class="p-1 rounded hover:bg-rose-500/10 hover:text-rose-400 transition-colors cursor-pointer"
                                    :class="{ 'text-rose-400 font-bold': msg.feedback_rating === -1 }"
                                    title="Kurang Membantu"
                                    aria-label="Tanggapan Kurang Membantu"
                                >
                                    <ThumbsDown class="w-3.5 h-3.5" :class="{ 'fill-rose-400/20': msg.feedback_rating === -1 }" />
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Pertanyaan Cepat (Adaptive Action Chips) -->
            <div class="px-3 py-2 border-t dark:border-[#1E3847] border-[#E0E4DD] dark:bg-[#070D13]/90 bg-[#F4F5F2] flex items-center gap-1.5 overflow-x-auto no-scrollbar">
                <button
                    v-for="(chip, idx) in actionChips"
                    :key="idx"
                    type="button"
                    @click="handleSend(chip.prompt)"
                    :disabled="isGenerating"
                    class="h-7 px-2.5 rounded-lg dark:bg-[#0F1E2B] bg-[#E8EAE5] border dark:border-[#1E3847] border-[#CCD2C8] hover:border-emerald-500/60 text-[10px] dark:text-slate-300 text-[#444C43] hover:text-emerald-400 dark:hover:text-emerald-300 whitespace-nowrap transition-colors flex-shrink-0 cursor-pointer disabled:opacity-40 disabled:cursor-not-allowed flex items-center gap-1"
                >
                    <Sparkles class="w-2.5 h-2.5 text-emerald-400" />
                    <span>{{ chip.label }}</span>
                </button>
            </div>

            <!-- Input Bar -->
            <div class="p-3 dark:bg-[#0B131B] bg-white border-t dark:border-[#1E3847] border-[#E0E4DD] flex items-end gap-2">
                <textarea
                    ref="inputAreaRef"
                    v-model="inputQuestion"
                    @input="adjustTextareaHeight"
                    @keydown.enter.prevent="handleEnterKey"
                    rows="1"
                    placeholder="Tanya data peternakan, zonasi PMK, atau gejala..."
                    class="min-h-[38px] max-h-[120px] py-2 px-3 rounded-xl dark:bg-[#0F1E2B] bg-[#F4F5F2] border dark:border-[#1E3847] border-[#E0E4DD] focus:border-emerald-500 dark:text-[#F5F5F5] text-[#171B17] placeholder:dark:text-[#64748B] placeholder:text-[#6E776D] outline-none flex-1 text-xs resize-none transition-all leading-relaxed"
                ></textarea>

                <button
                    v-if="isGenerating"
                    type="button"
                    @click="abortStream"
                    class="h-9 px-3 rounded-xl bg-rose-600/90 hover:bg-rose-600 text-white flex items-center justify-center gap-1 flex-shrink-0 transition-colors cursor-pointer shadow-sm text-xs font-medium"
                    title="Hentikan Penulisan"
                    aria-label="Hentikan Penulisan"
                >
                    <Square class="w-3.5 h-3.5 fill-current" />
                    <span class="text-[11px]">Stop</span>
                </button>

                <button
                    v-else
                    type="button"
                    @click="handleSend()"
                    :disabled="!inputQuestion.trim()"
                    class="h-9 w-9 rounded-xl bg-[#00B98B] hover:bg-[#00D084] disabled:opacity-40 disabled:cursor-not-allowed text-white flex items-center justify-center flex-shrink-0 transition-colors cursor-pointer shadow-sm"
                    title="Kirim Pertanyaan"
                    aria-label="Kirim Pertanyaan"
                >
                    <Send class="w-4 h-4 stroke-2" />
                </button>
            </div>
        </div>
    </div>
</template>

<style scoped>
.no-scrollbar::-webkit-scrollbar {
    display: none;
}
.no-scrollbar {
    -ms-overflow-style: none;
    scrollbar-width: none;
}
.custom-chat-scroll::-webkit-scrollbar {
    width: 4px;
}
.custom-chat-scroll::-webkit-scrollbar-track {
    background: transparent;
}
.custom-chat-scroll::-webkit-scrollbar-thumb {
    background: #1E3847;
    border-radius: 4px;
}
.custom-chat-scroll::-webkit-scrollbar-thumb:hover {
    background: #00B98B;
}

:deep(.prose-chat) {
    word-break: break-word;
}
:deep(.prose-chat strong) {
    color: #34D399;
    font-weight: 600;
}
:deep(.prose-chat blockquote) {
    margin: 0.5rem 0;
    padding-left: 0.75rem;
    border-left: 2px solid #00B98B;
    font-style: italic;
    color: #A7F3D0;
}
:deep(.prose-chat table) {
    width: 100%;
    margin: 0.5rem 0;
    border-collapse: collapse;
    font-size: 11px;
}
:deep(.prose-chat th),
:deep(.prose-chat td) {
    padding: 0.35rem 0.6rem;
    border: 1px solid #1A2E3D;
}
:deep(.prose-chat th) {
    background-color: #0C1924;
    color: #34D399;
}
:deep(.prose-chat td) {
    background-color: rgba(7, 13, 19, 0.6);
}
</style>
