<script setup lang="ts">
import { ref, watch, nextTick } from 'vue';
import { KECAMATAN_LIST, type KecamatanData, formatAngka } from '../../data/banyumasData';
import { X, Send, MessageSquareText } from 'lucide-vue-next';

const props = withDefaults(defineProps<{
    kecamatanList?: KecamatanData[];
    isOpen?: boolean;
}>(), {
    kecamatanList: () => [],
    isOpen: false
});

const emit = defineEmits<{
    (e: 'close'): void;
    (e: 'open'): void;
}>();

const isChatOpen = ref(props.isOpen ?? false);
const chatContainerRef = ref<HTMLDivElement | null>(null);

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

function formatMessage(text: string): string {
    const escaped = text
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
    return escaped.replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>');
}

const inputQuestion = ref('');
const messages = ref<{ sender: 'bot' | 'user'; text: string; time: string }[]>([
    {
        sender: 'bot',
        text: 'Pusat informasi data peternakan Kabupaten Banyumas. Tanyakan sebaran populasi, status zona PMK, atau fasilitas peternakan.',
        time: 'Baru saja'
    }
]);

const quickPrompts = [
    'Kecamatan apa dengan sapi potong terbanyak?',
    'Berapa total populasi sapi di Banyumas?',
    'Kecamatan mana yang masuk zona merah PMK?',
    'Di mana pusat produksi sapi perah?'
];

function handleSend(question?: string) {
    const q = question || inputQuestion.value.trim();
    if (!q) return;

    messages.value.push({
        sender: 'user',
        text: q,
        time: 'Baru saja'
    });
    inputQuestion.value = '';
    scrollToBottom();

    // Respons AI interaktif cerdas
    setTimeout(() => {
        let answer = '';
        const lowerQ = q.toLowerCase();
        const activeList = (props.kecamatanList && props.kecamatanList.length > 0) ? props.kecamatanList : KECAMATAN_LIST;

        if (lowerQ.includes('terbanyak') || lowerQ.includes('paling banyak')) {
            const top = activeList.length ? [...activeList].sort((a, b) => b.sapi_potong - a.sapi_potong)[0] : null;
            if (!top) {
                answer = 'Data kecamatan belum tersedia saat ini.';
            } else {
                answer = `Kecamatan dengan populasi sapi potong terbanyak saat ini adalah **${top.nama}** dengan total **${formatAngka(top.sapi_potong)} ekor**. Posisi kedua ditempati oleh Cilongok dengan 2.890 ekor.`;
            }
        } else if (lowerQ.includes('total') || lowerQ.includes('populasi')) {
            if (!activeList.length) {
                answer = 'Data kecamatan belum tersedia saat ini.';
            } else {
                const total = activeList.reduce((acc, k) => acc + k.sapi_potong, 0);
                answer = `Total populasi sapi potong di seluruh 27 kecamatan Kabupaten Banyumas mencapai **${formatAngka(total)} ekor**. Populasi ternak kambing juga tinggi dengan total 59.870 ekor.`;
            }
        } else if (lowerQ.includes('pmk') || lowerQ.includes('zona merah') || lowerQ.includes('penyakit')) {
            const bahayaKec = activeList.filter(k => k.zona === 'bahaya');
            const names = bahayaKec.map(k => k.nama).join(', ');
            answer = bahayaKec.length > 0
                ? `Saat ini ada **${bahayaKec.length} kecamatan** dalam zona merah bahaya PMK aktif: **${names}**. Disarankan pengawasan lalu lintas ternak ketat dan desinfeksi kandang rutin.`
                : 'Saat ini tidak ada kecamatan dalam zona merah bahaya PMK aktif di Kabupaten Banyumas.';
        } else if (lowerQ.includes('perah') || lowerQ.includes('susu')) {
            answer = `Sentra utama sapi perah dan produksi susu segar di Banyumas terpusat di lereng Gunung Slamet, khususnya di **Kecamatan Baturraden** (2.850 ekor) dan **Cilongok** (890 ekor).`;
        } else {
            answer = `Berdasarkan database WebGIS terkini, data peternakan di 27 kecamatan Kabupaten Banyumas tercatat aktif dan diverifikasi oleh Dinas Perikanan dan Peternakan Kabupaten Banyumas per Oktober 2026.`;
        }

        messages.value.push({
            sender: 'bot',
            text: answer,
            time: 'Baru saja'
        });
        scrollToBottom();
    }, 400);
}
</script>

<template>
    <div>
        <!-- 1. SLEEK CIRCULAR FLOATING ACTION BUTTON (FAB) - NON-AI-SLOP (BOTTOM-RIGHT) -->
        <!-- Tanya TERNAKVERSE AI FAB Launcher -->
        <button
            type="button"
            @click="toggleChat"
            class="fixed bottom-6 right-6 z-40 w-12 h-12 rounded-full shadow-2xl flex items-center justify-center transition-all duration-200 cursor-pointer bg-white border border-[#E0E4DD] hover:border-[#171B17] text-[#171B17] shadow-lg dark:bg-[#111111] dark:border dark:border-[#1E1E1E] dark:hover:border-[#00D084]/60 dark:text-[#F5F5F5] hover:dark:text-[#00D084]"
            :class="{ 'border-[#00D084] text-[#00D084] dark:bg-[#181818]': isChatOpen }"
            :title="isChatOpen ? 'Tutup Asisten' : 'Buka Asisten Data TERNAKVERSE AI'"
            :aria-label="isChatOpen ? 'Tutup Asisten' : 'Buka Asisten Data TERNAKVERSE AI'"
            data-widget="Tanya TERNAKVERSE AI"
        >
            <X v-if="isChatOpen" class="w-5 h-5 transition-transform duration-200" />
            <MessageSquareText v-else class="w-5 h-5 transition-transform duration-200" />
        </button>

        <!-- 2. FLOATING CHAT DRAWER WINDOW (WHEN OPEN) -->
        <div
            v-if="isChatOpen"
            class="fixed bottom-24 right-6 z-50 w-96 max-w-[calc(100vw-2rem)] h-[520px] dark:bg-[#080808] bg-white border dark:border-[#1E1E1E] border-[#E0E4DD] dark:text-[#F5F5F5] text-[#171B17] rounded-2xl shadow-2xl flex flex-col overflow-hidden animate-in zoom-in-95 duration-150 select-none backdrop-blur-xl"
        >
            <!-- Header Chat -->
            <div class="p-3.5 dark:bg-[#111111] bg-[#F4F5F2] border-b dark:border-[#1E1E1E] border-[#E0E4DD] flex items-center justify-between">
                <div class="flex items-center gap-2.5">
                    <div class="w-8 h-8 rounded-xl bg-emerald-500/15 border border-emerald-500/40 flex items-center justify-center text-[#00D084] shrink-0">
                        <MessageSquareText class="w-4 h-4 stroke-[2.2]" />
                    </div>
                    <div>
                        <h3 class="font-display font-bold text-sm dark:text-[#F5F5F5] text-[#171B17] flex items-center gap-1.5 leading-tight">
                            <span>TERNAKVERSE AI</span>
                        </h3>
                        <p class="text-[10px] dark:text-[#B3B3B3] text-[#6E776D] leading-tight mt-0.5">Asisten Data & Pengetahuan Spasial</p>
                    </div>
                </div>
                <button
                    type="button"
                    @click="closeChat"
                    class="w-8 h-8 rounded-lg dark:bg-[#181818] bg-[#F4F5F2] border dark:border-[#1E1E1E] border-[#E0E4DD] dark:text-[#B3B3B3] text-[#444C43] hover:text-rose-400 hover:border-rose-500/40 flex items-center justify-center transition-colors cursor-pointer"
                    aria-label="Tutup Asisten Data"
                    title="Tutup Chatbot (Esc)"
                >
                    <X class="w-4 h-4" />
                </button>
            </div>

            <!-- Area Percakapan -->
            <div ref="chatContainerRef" class="flex-1 p-3.5 overflow-y-auto space-y-3 custom-chat-scroll">
                <div
                    v-for="(msg, idx) in messages"
                    :key="idx"
                    class="flex items-start gap-2"
                    :class="{ 'flex-row-reverse': msg.sender === 'user' }"
                >
                    <div
                        class="w-7 h-7 rounded-lg flex items-center justify-center flex-shrink-0 text-[10px] font-bold"
                        :class="msg.sender === 'bot' ? 'bg-emerald-500/15 text-emerald-400 border border-emerald-500/30' : 'bg-teal-500/15 text-teal-300 border border-teal-500/30'"
                    >
                        {{ msg.sender === 'bot' ? 'AI' : 'Kamu' }}
                    </div>
                    <div
                        class="max-w-[82%] p-3 rounded-2xl text-xs leading-relaxed tabular-nums"
                        :class="msg.sender === 'bot'
                            ? 'dark:bg-[#181818] bg-[#F4F5F2] border dark:border-[#1E1E1E] border-[#E0E4DD] dark:text-[#F5F5F5] text-[#171B17] shadow-sm'
                            : 'dark:bg-[#181818] bg-[#F4F5F2] border dark:border-[#282828] border-[#CCD2C8] dark:text-white text-[#171B17] font-medium'"
                    >
                        <div v-if="msg.sender === 'bot'" v-html="formatMessage(msg.text)"></div>
                        <div v-else>{{ msg.text }}</div>
                        <span class="block text-[9px] mt-1 dark:text-[#B3B3B3] text-[#6E776D] text-right tabular-nums">{{ msg.time }}</span>
                    </div>
                </div>
            </div>

            <!-- Pertanyaan Cepat (Chips) -->
            <div class="px-3 py-2 border-t dark:border-[#1E1E1E] border-[#E0E4DD] dark:bg-[#111111]/70 bg-[#F4F5F2]/70 flex items-center gap-1.5 overflow-x-auto no-scrollbar">
                <button
                    v-for="(prompt, idx) in quickPrompts"
                    :key="idx"
                    type="button"
                    @click="handleSend(prompt)"
                    class="h-7 px-2.5 rounded-lg dark:bg-[#181818] bg-[#E8EAE5] border dark:border-[#1E1E1E] border-[#CCD2C8] hover:border-emerald-500/50 text-[10px] dark:text-[#B3B3B3] text-[#444C43] hover:text-emerald-500 dark:hover:text-emerald-300 whitespace-nowrap transition-colors flex-shrink-0 cursor-pointer"
                >
                    {{ prompt }}
                </button>
            </div>

            <!-- Input Bar -->
            <div class="p-3 dark:bg-[#111111] bg-white border-t dark:border-[#1E1E1E] border-[#E0E4DD] flex items-center gap-2">
                <input
                    v-model="inputQuestion"
                    @keydown.enter="handleSend()"
                    type="text"
                    placeholder="Tanya data peternakan"
                    class="h-9 px-3 rounded-xl dark:bg-[#181818] bg-[#F4F5F2] border dark:border-[#1E1E1E] border-[#E0E4DD] focus:border-emerald-500 dark:text-[#F5F5F5] text-[#171B17] placeholder:dark:text-[#B3B3B3] placeholder:text-[#6E776D] outline-none flex-1 text-xs"
                />
                <button
                    type="button"
                    @click="handleSend()"
                    class="h-9 w-9 rounded-xl bg-[#159B78] hover:bg-[#1BB18A] text-white flex items-center justify-center flex-shrink-0 transition-colors cursor-pointer shadow-sm"
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
    background: #090909;
    border-radius: 4px;
}
.custom-chat-scroll::-webkit-scrollbar-thumb {
    background: #1E1E1E;
    border-radius: 4px;
}
.custom-chat-scroll::-webkit-scrollbar-thumb:hover {
    background: #159B78;
}
</style>

