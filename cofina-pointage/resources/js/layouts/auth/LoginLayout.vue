<script setup lang="ts">
import AppLogo from '@/components/AppLogo.vue';
import { home } from '@/routes';
import { Link } from '@inertiajs/vue3';
import { Shield, Timer } from 'lucide-vue-next';

const year = new Date().getFullYear();
</script>

<template>
    <div class="flex min-h-svh items-center justify-center bg-sidebar p-4 sm:p-6 lg:p-10">
        <div
            class="grid w-full max-w-4xl overflow-hidden rounded-2xl border border-border bg-card shadow-2xl lg:grid-cols-2 lg:min-h-[520px]"
        >
            <!-- Panneau marque -->
            <div
                class="relative flex flex-col justify-between gap-8 overflow-hidden bg-gradient-to-br from-neutral-900 via-neutral-800 to-neutral-900 p-8 text-white sm:p-10"
            >
                <div
                    class="pointer-events-none absolute -top-16 -right-16 h-48 w-48 rounded-full bg-primary/20 blur-3xl"
                    aria-hidden="true"
                />
                <div
                    class="pointer-events-none absolute bottom-0 left-0 h-32 w-full bg-gradient-to-t from-black/20 to-transparent"
                    aria-hidden="true"
                />

                <Link :href="home()" class="relative z-10 w-fit transition-opacity hover:opacity-90">
                    <div class="rounded-xl bg-white p-4 shadow-lg">
                        <AppLogo class="max-w-[180px]" />
                    </div>
                </Link>

                <div class="relative z-10 space-y-4">
                    <div
                        class="inline-flex h-11 w-11 items-center justify-center rounded-lg bg-primary text-primary-foreground shadow-md"
                    >
                        <Timer class="h-5 w-5" />
                    </div>
                    <div>
                        <h1 class="text-2xl font-bold tracking-tight sm:text-3xl">COFINA Pointage</h1>
                        <p class="mt-2 max-w-xs text-sm leading-relaxed text-white/75">
                            Gérez vos heures et suivez la présence de vos collaborateurs.
                        </p>
                    </div>
                </div>

                <p class="relative z-10 text-xs text-white/50">
                    © {{ year }} Compagnie Financière Africaine
                </p>
            </div>

            <!-- Panneau formulaire -->
            <div class="flex flex-col justify-center p-8 sm:p-10">
                <div class="mb-8">
                    <h2 class="text-2xl font-bold text-foreground">Connexion</h2>
                    <p class="mt-1 text-sm text-muted-foreground">Veuillez entrer vos identifiants</p>
                </div>

                <slot />

                <div class="mt-8 flex items-center justify-center gap-2 border-t border-border pt-6 text-xs text-muted-foreground">
                    <Shield class="h-3.5 w-3.5 text-primary" />
                    <span>Sécurisé et confidentiel</span>
                </div>
            </div>
        </div>
    </div>
</template>
