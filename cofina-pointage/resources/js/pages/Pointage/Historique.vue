<script setup lang="ts">
import { Link, router } from '@inertiajs/vue3';
import PointageLayout from '@/layouts/pointage/PointageLayout.vue';
import type { BreadcrumbItem } from '@/types';
import { Check } from 'lucide-vue-next';
import { computed } from 'vue';

const props = defineProps<{
    pointages: {
        data: {
            date_display: string;
            arrivee: string;
            depart: string;
            heures: string;
            gps_ok: boolean;
            biometric_ok: boolean;
            statut: string;
            statut_label: string;
        }[];
        links?: { url: string | null; label: string; active: boolean }[];
        current_page: number;
        last_page: number;
    };
    summary: {
        card_periode_titre: string;
        total_heures_label: string;
        total_heures_sous_titre: string;
        jours_presents_label: string;
        jours_presents_sous_titre: string;
        heures_supp_label: string;
        heures_supp_sous_titre: string;
        retards_label: string;
        retards_sous_titre: string;
    };
    filters: {
        periode: string;
        statut: string;
    };
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Pointage', href: '/pointage' },
    { title: 'Mon historique', href: '#' },
];

const exportHref = computed(() => {
    const p = new URLSearchParams();
    p.set('periode', props.filters.periode);
    p.set('statut', props.filters.statut);
    return `/pointage/historique/export?${p.toString()}`;
});

function setStatut(v: string) {
    router.get(
        '/pointage/historique',
        { periode: props.filters.periode, statut: v },
        { preserveState: true, preserveScroll: true, replace: true },
    );
}

function tabHref(periode: string) {
    const p = new URLSearchParams();
    p.set('periode', periode);
    p.set('statut', props.filters.statut);
    return `/pointage/historique?${p.toString()}`;
}

function statutBadgeClass(statut: string): string {
    return statut === 'retard'
        ? 'border-[#FDBA74] bg-[#FFF4E6] text-[#C2410C]'
        : 'border-[#BBF7D0] bg-[#EAF3DE] text-[#166534]';
}
</script>

<template>
    <PointageLayout title="Mon historique" :breadcrumbs="breadcrumbs">
        <div class="mx-auto max-w-6xl space-y-6">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <h1 class="text-xl font-semibold text-[#0C447C]">Mon historique</h1>
                <a
                    :href="exportHref"
                    class="inline-flex items-center justify-center rounded-md border border-[#185FA5] bg-white px-4 py-2 text-sm font-medium text-[#185FA5] hover:bg-[#E6F1FB]"
                >
                    Exporter PDF/Excel
                </a>
            </div>

            <div class="flex flex-wrap gap-2 border-b border-[#e2e0d8] pb-3">
                <Link
                    :href="tabHref('mois')"
                    preserve-scroll
                    class="rounded-full px-4 py-1.5 text-sm font-medium transition-colors"
                    :class="
                        filters.periode === 'mois'
                            ? 'bg-[#185FA5] text-white'
                            : 'bg-[#FAFAF8] text-[#888780] hover:bg-[#F1EFE8]'
                    "
                >
                    Ce mois
                </Link>
                <Link
                    :href="tabHref('semaine')"
                    preserve-scroll
                    class="rounded-full px-4 py-1.5 text-sm font-medium transition-colors"
                    :class="
                        filters.periode === 'semaine'
                            ? 'bg-[#185FA5] text-white'
                            : 'bg-[#FAFAF8] text-[#888780] hover:bg-[#F1EFE8]'
                    "
                >
                    Cette semaine
                </Link>
                <Link
                    :href="tabHref('tout')"
                    preserve-scroll
                    class="rounded-full px-4 py-1.5 text-sm font-medium transition-colors"
                    :class="
                        filters.periode === 'tout'
                            ? 'bg-[#185FA5] text-white'
                            : 'bg-[#FAFAF8] text-[#888780] hover:bg-[#F1EFE8]'
                    "
                >
                    Tout l'historique
                </Link>
            </div>

            <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                <div class="rounded-[10px] border border-[#e2e0d8] bg-white p-4 shadow-sm">
                    <div class="text-[10px] font-bold uppercase tracking-wide text-[#888780]">
                        Total heures ({{ summary.card_periode_titre }})
                    </div>
                    <div class="mt-2 text-2xl font-bold tabular-nums text-[#0C447C]">{{ summary.total_heures_label }}</div>
                    <p class="mt-1 text-xs text-[#888780]">{{ summary.total_heures_sous_titre }}</p>
                </div>
                <div class="rounded-[10px] border border-[#e2e0d8] bg-white p-4 shadow-sm">
                    <div class="text-[10px] font-bold uppercase tracking-wide text-[#888780]">Jours présents</div>
                    <div class="mt-2 text-2xl font-bold tabular-nums text-[#0C447C]">{{ summary.jours_presents_label }}</div>
                    <p class="mt-1 text-xs text-[#888780]">{{ summary.jours_presents_sous_titre }}</p>
                </div>
                <div class="rounded-[10px] border border-[#e2e0d8] bg-white p-4 shadow-sm">
                    <div class="text-[10px] font-bold uppercase tracking-wide text-[#888780]">Heures supplémentaires</div>
                    <div class="mt-2 text-2xl font-bold tabular-nums text-[#0C447C]">{{ summary.heures_supp_label }}</div>
                    <p class="mt-1 text-xs text-[#888780]">{{ summary.heures_supp_sous_titre }}</p>
                </div>
                <div class="rounded-[10px] border border-[#e2e0d8] bg-white p-4 shadow-sm">
                    <div class="text-[10px] font-bold uppercase tracking-wide text-[#888780]">Retards cumulés</div>
                    <div class="mt-2 text-2xl font-bold tabular-nums text-[#0C447C]">{{ summary.retards_label }}</div>
                    <p class="mt-1 text-xs text-[#888780]">{{ summary.retards_sous_titre }}</p>
                </div>
            </div>

            <div class="overflow-hidden rounded-[10px] border border-[#e2e0d8] bg-white shadow-sm">
                <div class="flex flex-wrap items-center justify-between gap-3 border-b border-[#e2e0d8] bg-[#FAFAF8] px-4 py-3">
                    <h2 class="text-sm font-semibold text-[#0C447C]">Détail des pointages</h2>
                    <div class="flex items-center gap-2">
                        <label class="text-[11px] font-bold uppercase text-[#888780]">Filtrer</label>
                        <select
                            :value="filters.statut"
                            class="rounded-md border border-[#e2e0d8] bg-white px-2 py-1.5 text-xs text-[#0C447C]"
                            @change="setStatut(($event.target as HTMLSelectElement).value)"
                        >
                            <option value="tous">Tous statuts</option>
                            <option value="normal">Normal</option>
                            <option value="retard">Retard</option>
                        </select>
                    </div>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full min-w-[840px] text-sm">
                        <thead class="border-b border-[#e2e0d8] bg-[#FAFAF8] text-left text-[10px] font-bold uppercase tracking-wide text-[#888780]">
                            <tr>
                                <th class="px-4 py-3">Date</th>
                                <th class="px-4 py-3">Arrivée</th>
                                <th class="px-4 py-3">Départ</th>
                                <th class="px-4 py-3">Heures</th>
                                <th class="px-4 py-3 text-center">GPS</th>
                                <th class="px-4 py-3 text-center">Biom.</th>
                                <th class="px-4 py-3">Statut</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="(p, idx) in pointages.data" :key="idx + '-' + p.date_display" class="border-b border-[#F1EFE8] last:border-0 hover:bg-[#FAFAF8]/80">
                                <td class="whitespace-nowrap px-4 py-3 font-mono text-xs text-[#0C447C]">{{ p.date_display }}</td>
                                <td class="px-4 py-3 tabular-nums">{{ p.arrivee }}</td>
                                <td class="px-4 py-3 tabular-nums">{{ p.depart }}</td>
                                <td class="px-4 py-3 tabular-nums">{{ p.heures }}</td>
                                <td class="px-4 py-3 text-center">
                                    <Check v-if="p.gps_ok" class="mx-auto inline h-5 w-5 text-[#3B6D11]" aria-label="GPS OK" />
                                    <span v-else class="text-[#ccc]">—</span>
                                </td>
                                <td class="px-4 py-3 text-center">
                                    <Check v-if="p.biometric_ok" class="mx-auto inline h-5 w-5 text-[#3B6D11]" aria-label="Biométrie OK" />
                                    <span v-else class="text-[#ccc]">—</span>
                                </td>
                                <td class="px-4 py-3">
                                    <span
                                        class="inline-flex rounded-full border px-2.5 py-0.5 text-[11px] font-semibold"
                                        :class="statutBadgeClass(p.statut)"
                                    >
                                        {{ p.statut_label }}
                                    </span>
                                </td>
                            </tr>
                            <tr v-if="!pointages.data?.length">
                                <td colspan="7" class="px-4 py-12 text-center text-[#888780]">Aucun pointage sur cette période.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div v-if="(pointages.last_page ?? 1) > 1 && pointages.links?.length" class="flex flex-wrap justify-center gap-1 border-t border-[#e2e0d8] px-4 py-3">
                    <template v-for="(link, i) in pointages.links ?? []" :key="i">
                        <Link
                            v-if="link.url"
                            :href="link.url"
                            preserve-scroll
                            class="min-w-[2.25rem] rounded-md px-2 py-1 text-center text-xs"
                            :class="
                                link.active
                                    ? 'bg-[#185FA5] font-semibold text-white'
                                    : 'border border-[#e2e0d8] text-[#0C447C] hover:bg-[#FAFAF8]'
                            "
                        >
                            <span v-html="link.label" />
                        </Link>
                        <span
                            v-else
                            class="min-w-[2.25rem] cursor-not-allowed rounded-md px-2 py-1 text-center text-xs text-[#ccc]"
                            v-html="link.label"
                        />
                    </template>
                </div>
            </div>

            <Link href="/pointage" class="text-sm text-[#185FA5] underline hover:no-underline">← Retour au tableau de bord</Link>
        </div>
    </PointageLayout>
</template>
