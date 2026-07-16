<script setup lang="ts">
import AppLogo from '@/components/AppLogo.vue';
import { home } from '@/routes';
import { Link } from '@inertiajs/vue3';
import { Timer } from 'lucide-vue-next';

defineProps<{
    title?: string;
    description?: string;
}>();
</script>

<template>
    <div class="flex min-h-svh flex-col items-center justify-center bg-sidebar p-6 md:p-10">
        <div class="w-full max-w-md">
            <div class="overflow-hidden rounded-2xl border border-border bg-card shadow-xl">
                <div class="h-1 w-full bg-primary" />

                <div class="p-8 md:p-10">
                    <Link
                        :href="home()"
                        class="mb-8 flex justify-center transition-opacity hover:opacity-90"
                    >
                        <AppLogo class="max-w-[240px]" />
                    </Link>

                    <div class="mb-8 text-center">
                        <div
                            class="mx-auto mb-4 flex h-12 w-12 items-center justify-center rounded-xl bg-primary/10 text-primary"
                        >
                            <Timer class="h-6 w-6" />
                        </div>
                        <h1 class="text-2xl font-bold tracking-tight text-foreground">
                            {{ title || 'Connexion' }}
                        </h1>
                        <p class="mt-2 text-sm leading-relaxed text-muted-foreground">
                            {{
                                description ||
                                'Accédez à COFINA Pointage & Présence pour gérer les pointages, la présence et les paramètres RH.'
                            }}
                        </p>
                    </div>

                    <slot />
                </div>
            </div>

            <p class="mt-6 text-center text-xs text-muted-foreground">
                COFINA Pointage — Compagnie Financière Africaine
            </p>
        </div>
    </div>
</template>
