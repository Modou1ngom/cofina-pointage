<script setup lang="ts">
import PointageLayout from '@/layouts/pointage/PointageLayout.vue';
import type { BreadcrumbItem } from '@/types';
import { Link } from '@inertiajs/vue3';
import { Fingerprint, MapPin, MousePointer2 } from 'lucide-vue-next';

defineProps<{
    kpis: {
        heures_mois: number;
        delta_heures_vs_mois_precedent: number;
        jours_presents: number;
        jours_ouvres_mois: number;
        retards: number;
        penalty_retard_fcfa: number;
        solde_conges_jours: number;
        conges_a_prendre_jours: number;
    };
    journees: {
        date: string;
        arrivee: string;
        depart: string;
        heures: string;
        statut: string;
    }[];
    activites: {
        kind: string;
        title: string;
        subtitle: string;
        lieu?: string;
        detail?: string;
        has_gps?: boolean;
        has_bio?: boolean;
    }[];
    pendingDeclarations: number;
    rappelDepart: { heure: string; minutes_avant: number };
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Pointage', href: '/pointage' },
    { title: 'Tableau de bord', href: '#' },
];

function statutBadge(statut: string): string {
    if (statut === 'retard') {
        return 'border-[#FDBA74] bg-[#FFF4E6] text-[#C2410C]';
    }

    return 'border-[#BBF7D0] bg-[#EAF3DE] text-[#166534]';
}

function statutLabel(statut: string): string {
    return statut === 'retard' ? 'Retard' : 'Normal';
}

function formatDateRow(iso: string): string {
    const [y, m, d] = iso.split('-').map(Number);
    if (!y || !m || !d) {
        return iso;
    }

    return `${y}-${String(m).padStart(2, '0')}-${String(d).padStart(2, '0')}`;
}

function deltaLabel(delta: number): string {
    if (delta === 0) {
        return '= mois dernier (à date)';
    }
    if (delta > 0) {
        return `↑ ${delta}h vs mois dernier`;
    }

    return `↓ ${Math.abs(delta)}h vs mois dernier`;
}
</script>

<template>
    <PointageLayout title="Tableau de bord" :breadcrumbs="breadcrumbs">
        <div class="mx-auto max-w-6xl space-y-6">
            <div class="flex flex-wrap items-center justify-between gap-4">
                <h1 class="text-xl font-semibold tracking-tight text-[#0C447C]">Tableau de bord</h1>
                <Link
                    href="/pointage/pointer"
                    class="inline-flex items-center gap-2 rounded-lg bg-[#185FA5] px-4 py-2.5 text-sm font-medium text-white shadow-sm transition hover:bg-[#144a84]"
                >
                    <MousePointer2 class="size-4 opacity-90" aria-hidden="true" />
                    Pointer maintenant
                </Link>
            </div>

            <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
                <div class="rounded-[10px] border border-[#e2e0d8] bg-white p-5 shadow-sm">
                    <div class="text-[11px] font-semibold uppercase tracking-wide text-[#888780]">Heures ce mois</div>
                    <div class="mt-2 text-3xl font-semibold text-[#0C447C]">{{ kpis.heures_mois }}h</div>
                    <div class="mt-1 text-xs text-[#64748B]">{{ deltaLabel(kpis.delta_heures_vs_mois_precedent) }}</div>
                </div>
                <div class="rounded-[10px] border border-[#e2e0d8] bg-white p-5 shadow-sm">
                    <div class="text-[11px] font-semibold uppercase tracking-wide text-[#888780]">Jours présents</div>
                    <div class="mt-2 text-3xl font-semibold text-[#0C447C]">{{ kpis.jours_presents }}</div>
                    <div class="mt-1 text-xs text-[#64748B]">sur {{ kpis.jours_ouvres_mois }} ouvrés</div>
                </div>
                <div class="rounded-[10px] border border-[#e2e0d8] bg-white p-5 shadow-sm">
                    <div class="text-[11px] font-semibold uppercase tracking-wide text-[#888780]">Retards</div>
                    <div class="mt-2 text-3xl font-semibold text-[#0C447C]">{{ kpis.retards }}</div>
                    <div class="mt-1 text-xs text-[#64748B]">
                        <template v-if="kpis.retards > 0">Validé — −{{ kpis.penalty_retard_fcfa.toLocaleString('fr-FR') }} FCFA</template>
                        <template v-else>Aucun retard ce mois-ci</template>
                    </div>
                </div>
                <div class="rounded-[10px] border border-[#e2e0d8] bg-white p-5 shadow-sm">
                    <div class="text-[11px] font-semibold uppercase tracking-wide text-[#888780]">Solde congés</div>
                    <div class="mt-2 text-3xl font-semibold text-[#0C447C]">{{ kpis.solde_conges_jours }}j</div>
                    <div class="mt-1 text-xs text-[#64748B]">{{ kpis.conges_a_prendre_jours }}j à prendre avant mars</div>
                </div>
            </div>

            <div class="grid gap-6 lg:grid-cols-12 lg:items-start">
                <div class="lg:col-span-7">
                    <div class="overflow-hidden rounded-[10px] border border-[#e2e0d8] bg-white shadow-sm">
                        <div class="border-b border-[#e2e0d8] px-4 py-3 text-sm font-semibold text-[#0C447C]">
                            Mes derniers pointages
                        </div>
                        <div class="overflow-x-auto">
                            <table class="w-full min-w-[520px] text-sm">
                                <thead class="bg-[#FAFAF8] text-left text-[10px] font-bold uppercase tracking-wide text-[#888780]">
                                    <tr>
                                        <th class="px-4 py-3">Date</th>
                                        <th class="px-4 py-3">Arrivée</th>
                                        <th class="px-4 py-3">Départ</th>
                                        <th class="px-4 py-3">Heures</th>
                                        <th class="px-4 py-3">Statut</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="j in journees" :key="j.date" class="border-t border-[#F1EFE8]">
                                        <td class="px-4 py-3 font-mono text-xs tabular-nums text-slate-800">{{ formatDateRow(j.date) }}</td>
                                        <td class="px-4 py-3 font-mono text-xs">{{ j.arrivee }}</td>
                                        <td class="px-4 py-3 font-mono text-xs">{{ j.depart }}</td>
                                        <td class="px-4 py-3 text-slate-700">{{ j.heures }}</td>
                                        <td class="px-4 py-3">
                                            <span
                                                class="inline-flex rounded-full border px-2.5 py-0.5 text-[11px] font-semibold"
                                                :class="statutBadge(j.statut)"
                                            >
                                                {{ statutLabel(j.statut) }}
                                            </span>
                                        </td>
                                    </tr>
                                    <tr v-if="!journees?.length">
                                        <td colspan="5" class="px-4 py-10 text-center text-[#888780]">Aucun pointage récent.</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <div class="lg:col-span-5">
                    <div class="overflow-hidden rounded-[10px] border border-[#e2e0d8] bg-white shadow-sm">
                        <div class="border-b border-[#e2e0d8] px-4 py-3 text-sm font-semibold text-[#0C447C]">Activité récente</div>
                        <ul class="divide-y divide-[#F1EFE8] px-4 py-2">
                            <li v-for="(a, idx) in activites" :key="idx" class="py-4">
                                <div class="text-sm font-semibold text-slate-900">{{ a.title }}</div>
                                <div class="mt-0.5 text-xs text-[#64748B]">{{ a.subtitle }}</div>
                                <div v-if="a.detail" class="mt-1 text-xs text-slate-600">{{ a.detail }}</div>
                                <div
                                    v-if="a.kind === 'pointage_arrivee' && (a.lieu || a.has_gps || a.has_bio)"
                                    class="mt-2 flex flex-wrap items-center gap-3 text-xs text-[#64748B]"
                                >
                                    <span v-if="a.lieu">{{ a.lieu }}</span>
                                    <span
                                        v-if="a.has_gps"
                                        class="inline-flex items-center justify-center rounded-full border border-[#BBF7D0] bg-[#F0FDF4] p-1 text-[#16A34A]"
                                        title="GPS conforme"
                                    >
                                        <MapPin class="size-3.5" aria-hidden="true" />
                                    </span>
                                    <span
                                        v-if="a.has_bio"
                                        class="inline-flex items-center justify-center rounded-full border border-[#BBF7D0] bg-[#F0FDF4] p-1 text-[#16A34A]"
                                        title="Biométrie OK"
                                    >
                                        <Fingerprint class="size-3.5" aria-hidden="true" />
                                    </span>
                                </div>
                            </li>
                            <li v-if="!activites?.length" class="py-8 text-center text-sm text-[#888780]">Aucune activité récente.</li>
                        </ul>
                    </div>
                </div>
            </div>

            <div
                class="rounded-lg border border-[#B5D4F4] bg-[#E6F1FB] px-4 py-3 text-sm text-[#0C447C]"
                role="status"
            >
                Prochain pointage de départ prévu à
                <strong>{{ rappelDepart.heure }}</strong>
                . Un rappel push sera envoyé {{ rappelDepart.minutes_avant }} min avant.
                <span v-if="pendingDeclarations > 0" class="mt-1 block text-xs text-[#854F0B]">
                    {{ pendingDeclarations }} déclaration(s) en cours de validation.
                </span>
            </div>

            <div class="flex flex-wrap gap-4 text-sm">
                <Link href="/pointage/historique" class="font-medium text-[#185FA5] underline hover:no-underline">Voir l'historique complet</Link>
                <Link href="/pointage/declarations" class="font-medium text-[#185FA5] underline hover:no-underline">Mes déclarations</Link>
                <Link
                    href="/pointage/pointer"
                    class="inline-flex items-center gap-2 rounded-lg bg-[#185FA5] px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-[#154a84]"
                >
                    <MousePointer2 class="size-4 opacity-90" aria-hidden="true" />
                    Pointer maintenant
                </Link>
            </div>
        </div>
    </PointageLayout>
</template>
