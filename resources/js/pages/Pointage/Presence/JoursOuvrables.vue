<script setup lang="ts">
import PointageLayout from '@/layouts/pointage/PointageLayout.vue';
import type { BreadcrumbItem } from '@/types';
import { Link, router, useForm, usePage } from '@inertiajs/vue3';
import { computed, watch } from 'vue';

type JourRow = {
    day_of_week: number;
    est_ouvrable: boolean;
    heure_debut: string;
    heure_fin: string;
    duree_theorique_heures: string | number;
};

type Profile = {
    id: number;
    libelle: string;
    scope_type: string;
    jours_semaine?: JourRow[];
    weekend_jours?: number[];
    weekend_samedi_matin_ouvrable?: boolean;
    weekend_samedi_matin_fin?: string | null;
    weekend_dimanche_matin_ouvrable?: boolean;
    weekend_dimanche_matin_fin?: string | null;
    weekend_travail_majoration_pct?: number | string;
};

const props = defineProps<{
    profiles: Profile[];
    selected_profile_id: number;
    profile: Profile;
    departements: { id: number; nom: string }[];
    profils: { id: number; nom: string; prenom: string; matricule: string | null; departement: string | null }[];
}>();

const page = usePage<{ flash?: { success?: string } }>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Pointage & Présence', href: '/pointage/rh/presence/recuperation-pointages' },
    { title: 'Configuration', href: '#' },
    { title: 'Jour ouvrable', href: '#' },
    { title: 'Définition des jours ouvrables', href: '#' },
];

const dowOrder = [1, 2, 3, 4, 5, 6, 0];
const dowLabels: Record<number, string> = {
    1: 'Lundi',
    2: 'Mardi',
    3: 'Mercredi',
    4: 'Jeudi',
    5: 'Vendredi',
    6: 'Samedi',
    0: 'Dimanche',
};

function sliceTime(v: string | null | undefined): string {
    if (!v) return '';
    return String(v).slice(0, 5);
}

function buildJours(profile: Profile): JourRow[] {
    const raw = profile.jours_semaine ?? [];
    const by = Object.fromEntries(raw.map((x) => [x.day_of_week, x]));
    return dowOrder.map((dow) => ({
        day_of_week: dow,
        est_ouvrable: Boolean(by[dow]?.est_ouvrable),
        heure_debut: sliceTime(by[dow]?.heure_debut as string),
        heure_fin: sliceTime(by[dow]?.heure_fin as string),
        duree_theorique_heures: by[dow]?.duree_theorique_heures ?? '',
    }));
}

const form = useForm({
    profile_id: props.selected_profile_id,
    jours: buildJours(props.profile),
});

watch(
    () => props.profile,
    (p) => {
        form.profile_id = props.selected_profile_id;
        form.jours = buildJours(p);
    },
    { deep: true },
);

watch(
    () => props.selected_profile_id,
    (id) => {
        form.profile_id = id;
    },
);

const flashOk = computed(() => page.props.flash?.success);

function changeProfile(id: number) {
    router.get('/pointage/rh/presence/jours-ouvrables', { profile_id: id }, { preserveScroll: true, replace: true });
}

function submit() {
    form.post('/pointage/rh/presence/jours-ouvrables', { preserveScroll: true });
}
</script>

<template>
    <PointageLayout title="Définition des jours ouvrables" :breadcrumbs="breadcrumbs">
        <div class="mx-auto max-w-5xl space-y-6">
            <div>
                <h1 class="text-xl font-semibold text-[#0C447C]">Définition des jours ouvrables</h1>
                <p class="mt-1 text-sm text-[#5c5a57]">
          
                </p>
            </div>

            <p v-if="flashOk" class="rounded-lg border border-[#C0DD97] bg-[#EAF3DE] px-3 py-2 text-sm text-[#27500A]">{{ flashOk }}</p>

            <div class="flex flex-wrap items-end gap-3 rounded-xl border border-[#e2e0d8] bg-white p-4 shadow-sm">
                <div>
                    <label class="text-[11px] font-bold uppercase text-[#888780]">Profil horaire</label>
                    <select
                        class="mt-1 block rounded-lg border border-[#e2e0d8] px-3 py-2 text-sm text-[#0C447C]"
                        :value="selected_profile_id"
                        @change="changeProfile(Number(($event.target as HTMLSelectElement).value))"
                    >
                        <option v-for="p in profiles" :key="p.id" :value="p.id">{{ p.libelle }} ({{ p.scope_type }})</option>
                    </select>
                </div>
                <Link href="/pointage/rh/presence/week-ends" class="text-sm font-medium text-[#185FA5] underline">Week-ends →</Link>
                <Link href="/pointage/rh/presence/jours-feries-calendrier" class="text-sm font-medium text-[#185FA5] underline">Calendrier →</Link>
            </div>

            <form class="space-y-4 rounded-xl border border-[#e2e0d8] bg-white p-6 shadow-sm" @submit.prevent="submit">
                <table class="w-full min-w-[640px] text-sm">
                    <thead class="border-b border-[#e2e0d8] bg-[#FAFAF8] text-left text-[10px] font-bold uppercase text-[#888780]">
                        <tr>
                            <th class="px-3 py-2">Jour</th>
                            <th class="px-3 py-2">Ouvrable</th>
                            <th class="px-3 py-2">Début</th>
                            <th class="px-3 py-2">Fin</th>
                            <th class="px-3 py-2">Durée théorique (h)</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="row in form.jours" :key="row.day_of_week" class="border-b border-[#F1EFE8]">
                            <td class="px-3 py-2 font-medium text-[#0C447C]">{{ dowLabels[row.day_of_week] }}</td>
                            <td class="px-3 py-2">
                                <input v-model="row.est_ouvrable" type="checkbox" class="rounded border-[#e2e0d8]" />
                            </td>
                            <td class="px-3 py-2">
                                <input v-model="row.heure_debut" type="time" class="w-full rounded border border-[#e2e0d8] px-2 py-1" />
                            </td>
                            <td class="px-3 py-2">
                                <input v-model="row.heure_fin" type="time" class="w-full rounded border border-[#e2e0d8] px-2 py-1" />
                            </td>
                            <td class="px-3 py-2">
                                <input
                                    v-model="row.duree_theorique_heures"
                                    type="number"
                                    step="0.25"
                                    min="0"
                                    max="24"
                                    class="w-full rounded border border-[#e2e0d8] px-2 py-1"
                                />
                            </td>
                        </tr>
                    </tbody>
                </table>
                <div class="flex justify-end gap-2">
                    <button
                        type="submit"
                        class="rounded-lg bg-[#185FA5] px-5 py-2 text-sm font-semibold text-white hover:bg-[#144a84] disabled:opacity-50"
                        :disabled="form.processing"
                    >
                        Enregistrer
                    </button>
                </div>
            </form>

            <p v-if="form.hasErrors" class="text-sm text-red-600">Vérifiez les horaires saisis.</p>
        </div>
    </PointageLayout>
</template>
