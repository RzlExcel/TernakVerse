import { router } from '@inertiajs/vue3';
import { toast } from 'vue-sonner';
import type { FlashToast } from '@/types/ui';

export function initializeFlashToast(): void {
    const onEvent = Reflect.get(router, 'on') as (
        event: string,
        callback: (e: Event) => void,
    ) => void;

    onEvent.call(router, 'flash', (event: Event) => {
        const flash = (event as CustomEvent).detail?.flash;
        const data = flash?.toast as FlashToast | undefined;

        if (!data) {
            return;
        }

        toast[data.type](data.message);
    });
}
