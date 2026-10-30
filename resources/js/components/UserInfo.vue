<script setup lang="ts">
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { useInitials } from '@/composables/useInitials';
import type { User } from '@/types';
import { computed } from 'vue';

<<<<<<< Updated upstream
interface Props {
    user: User;
=======
type Props = {
    user?: User | null;
>>>>>>> Stashed changes
    showEmail?: boolean;
}

const props = withDefaults(defineProps<Props>(), {
    showEmail: false,
});

const { getInitials } = useInitials();

// Compute whether we should show the avatar image
<<<<<<< Updated upstream
const showAvatar = computed(() => props.user.avatar && props.user.avatar !== '');
=======
const showAvatar = computed(
    () => props.user?.avatar && props.user.avatar !== '',
);
>>>>>>> Stashed changes
</script>

<template>
    <Avatar class="h-8 w-8 overflow-hidden rounded-lg">
<<<<<<< Updated upstream
        <AvatarImage v-if="showAvatar" :src="user.avatar" :alt="user.name" />
=======
        <AvatarImage v-if="showAvatar && user" :src="user.avatar!" :alt="user.name" />
>>>>>>> Stashed changes
        <AvatarFallback class="rounded-lg text-black dark:text-white">
            {{ user ? getInitials(user.name) : 'TV' }}
        </AvatarFallback>
    </Avatar>

    <div class="grid flex-1 text-left text-sm leading-tight">
<<<<<<< Updated upstream
        <span class="truncate font-medium">{{ user.name }}</span>
        <span v-if="showEmail" class="truncate text-xs text-muted-foreground">{{ user.email }}</span>
=======
        <span class="truncate font-medium">{{ user ? user.name : 'Pengguna' }}</span>
        <span v-if="showEmail && user" class="truncate text-xs text-muted-foreground">{{
            user.email
        }}</span>
>>>>>>> Stashed changes
    </div>
</template>
