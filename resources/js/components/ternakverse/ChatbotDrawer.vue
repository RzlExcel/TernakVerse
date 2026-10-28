<script setup lang="ts">
import { ref } from 'vue';
import { KecamatanData, formatAngka } from '../../data/banyumasData';
import { X, Send, Bot, User, Sparkles } from 'lucide-vue-next';

const props = defineProps<{
    kecamatanList: KecamatanData[];
}>();

const emit = defineEmits<{
    (e: 'close'): void;
}>();

const inputQuestion = ref('');
const messages = ref<{ sender: 'bot' | 'user'; text: string; time: string }[]>([
    {
        sender: 'bot',
        text: 'Hai! Tanya apa nih soal data peternakan Kabupaten Banyumas? Kamu bisa tanya sebaran populasi, status zona PMK, atau kecamatan dengan sapi terbanyak!',
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

    // Respons AI interaktif cerdas
    setTimeout(() => {
        let answer = '';
        const lowerQ = q.toLowerCase();

        if (lowerQ.includes('terbanyak') || lowerQ.includes('paling banyak')) {
            const top = [...props.kecamatanList].sort((a, b) => b.sapi_potong - a.sapi_potong)[0];
            answer = `Kecamatan dengan populasi sapi potong terbanyak saat ini adalah **${top.nama}** dengan total **${formatAngka(top.sapi_potong)} ekor**. Posisi kedua ditempati oleh Cilongok dengan 2.890 ekor.`;
        } else if (lowerQ.includes('total') || lowerQ.includes('populasi')) {
            const total = props.kecamatanList.reduce((acc, k) => acc + k.sapi_potong, 0);
            answer = `Total populasi sapi potong di seluruh 27 kecamatan Kabupaten Banyumas mencapai **${formatAngka(total)} ekor**. Populasi ternak kambing juga tinggi dengan total 59.870 ekor.`;
        } else if (lowerQ.includes('pmk') || lowerQ.includes('zona merah') || lowerQ.includes('penyakit')) {
            const bahayaKec = props.kecamatanList.filter(k => k.zona === 'bahaya');
            const names = bahayaKec.map(k => k.nama).join(', ');
            answer = `Saat ini ada **${bahayaKec.length} kecamatan** dalam zona merah bahaya PMK aktif: **${names}**. Disarankan pengawasan lalu lintas ternak ketat dan desinfeksi kandang rutin.`;
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
    }, 400);
}
</script>

<template>
    <!-- Overlay with Backdrop Blur -->
    <div
        class="fixed inset-0 z-50 bg-[#080C0E]/80 backdrop-blur-md flex items-center justify-center p-4"
        @click.self="$emit('close')"
    >
        <!-- Modal Drawer -->
        <div class="glass-card border border-emerald-500/30 rounded-20 w-full max-w-lg h-[540px] flex flex-col shadow-2xl overflow-hidden select-none animate-in zoom-in-95 duration-150">
            <!-- Header Chat -->
            <div class="p-4 bg-[#080C0E]/70 border-b border-white/10 flex items-center justify-between">
                <div class="flex items-center gap-2.5">
                    <div class="w-9 h-9 rounded-full bg-emerald-500/10 border border-emerald-500/30 flex items-center justify-center text-emerald-400">
                        <Bot class="w-5 h-5 stroke-[2.2]" />
                    </div>
                    <div>
                        <h3 class="font-display font-extrabold text-base text-[#F4F7F5]">Asisten Data Ternakverse</h3>
                        <p class="text-[11px] text-slate-400">Tanya jawab cerdas seputar data peternakan Banyumas</p>
                    </div>
                </div>
                <button
                    @click="$emit('close')"
                    class="w-8 h-8 rounded-full bg-[#080C0E]/50 border border-white/10 text-slate-400 hover:text-[#F4F7F5] hover:border-rose-400/50 flex items-center justify-center transition-colors"
                >
                    <X class="w-4 h-4" />
                </button>
            </div>

            <!-- Area Percakapan -->
            <div class="flex-1 p-4 overflow-y-auto space-y-3">
                <div
                    v-for="(msg, idx) in messages"
                    :key="idx"
                    class="flex items-start gap-2.5"
                    :class="{ 'flex-row-reverse': msg.sender === 'user' }"
                >
                    <div
                        class="w-7 h-7 rounded-full flex items-center justify-center flex-shrink-0 text-xs font-bold"
                        :class="msg.sender === 'bot' ? 'bg-emerald-500/20 text-emerald-400 border border-emerald-500/30' : 'bg-teal-500/20 text-teal-300 border border-teal-500/30'"
                    >
                        {{ msg.sender === 'bot' ? 'AI' : 'Kamu' }}
                    </div>
                    <div
                        class="max-w-[80%] p-3 rounded-16 text-xs leading-relaxed"
                        :class="msg.sender === 'bot' 
                            ? 'bg-[#080C0E]/70 border border-white/10 text-[#F4F7F5]' 
                            : 'bg-emerald-500/15 border border-emerald-500/30 text-emerald-100 font-medium'"
                    >
                        <div v-html="msg.text.replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>')"></div>
                        <span class="block text-[10px] mt-1 opacity-60 text-right">{{ msg.time }}</span>
                    </div>
                </div>
            </div>

            <!-- Pertanyaan Cepat (Chips) -->
            <div class="px-4 py-2 border-t border-white/10 bg-[#080C0E]/50 flex items-center gap-1.5 overflow-x-auto no-scrollbar">
                <button
                    v-for="(prompt, idx) in quickPrompts"
                    :key="idx"
                    @click="handleSend(prompt)"
                    class="px-2.5 py-1 rounded-full bg-[#080C0E]/60 border border-white/10 hover:border-emerald-500/40 text-[11px] text-slate-400 hover:text-emerald-300 whitespace-nowrap transition-colors flex-shrink-0"
                >
                    {{ prompt }}
                </button>
            </div>

            <!-- Input Bar -->
            <div class="p-3 bg-[#080C0E]/80 border-t border-white/10 flex items-center gap-2">
                <input
                    v-model="inputQuestion"
                    @keydown.enter="handleSend()"
                    type="text"
                    placeholder="Ketik pertanyaanmu di sini..."
                    class="input-tactile flex-1 text-xs"
                />
                <button
                    @click="handleSend()"
                    class="btn-primary-tactile h-10 w-10 p-0 flex items-center justify-center flex-shrink-0"
                >
                    <Send class="w-4 h-4 stroke-[2.5]" />
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
</style>
