<script setup lang="ts">
import type { InertiaForm } from '@inertiajs/vue3';

export interface DeclarationFormShape {
    type: string;
    date_concernee: string;
    motif: string;
    commentaire: string;
    justificatif: File | null;
}

const form = defineModel<InertiaForm<DeclarationFormShape>>('form', { required: true });

const MOTIFS = [
    'Transport perturbé',
    'Réunion imprévue',
    'Rendez-vous médical',
    'Panne véhicule',
    'Grève / perturbation transport',
    'Maladie',
    'Mission extérieure',
    'Formation',
    'Congé sans solde',
    'Autre (préciser dans le commentaire)',
];

function onFile(e: Event) {
    const t = e.target as HTMLInputElement;
    form.value.justificatif = t.files?.[0] ?? null;
}
</script>

<template>
    <div class="space-y-4">
        <div>
            <label class="text-[11px] font-bold uppercase tracking-wide text-[#888780]">Type de déclaration</label>
            <select v-model="form.type" class="mt-1 w-full rounded-md border border-[#e2e0d8] bg-white px-3 py-2 text-sm text-[#0C447C]">
                <option value="retard">Retard</option>
                <option value="absence">Absence</option>
                <option value="conge">Congé</option>
                <option value="regularisation">Régularisation</option>
            </select>
            <p v-if="form.errors.type" class="mt-1 text-sm text-[#A32D2D]">{{ form.errors.type }}</p>
        </div>
        <div>
            <label class="text-[11px] font-bold uppercase tracking-wide text-[#888780]">Date concernée</label>
            <input v-model="form.date_concernee" type="date" class="mt-1 w-full rounded-md border border-[#e2e0d8] px-3 py-2 text-sm" />
            <p v-if="form.errors.date_concernee" class="mt-1 text-sm text-[#A32D2D]">{{ form.errors.date_concernee }}</p>
        </div>
        <div>
            <label class="text-[11px] font-bold uppercase tracking-wide text-[#888780]">Motif</label>
            <select v-model="form.motif" class="mt-1 w-full rounded-md border border-[#e2e0d8] bg-white px-3 py-2 text-sm">
                <option disabled value="">Sélectionner un motif</option>
                <option v-for="m in MOTIFS" :key="m" :value="m">{{ m }}</option>
            </select>
            <p v-if="form.errors.motif" class="mt-1 text-sm text-[#A32D2D]">{{ form.errors.motif }}</p>
        </div>
        <div>
            <label class="text-[11px] font-bold uppercase tracking-wide text-[#888780]">Justificatif (PDF, JPG, PNG — max 5 Mo)</label>
            <input type="file" accept=".pdf,.jpg,.jpeg,.png" class="mt-1 w-full text-sm file:mr-3 file:rounded-md file:border-0 file:bg-[#E6F1FB] file:px-3 file:py-1.5 file:text-sm file:font-medium file:text-[#185FA5]" @change="onFile" />
            <p v-if="form.errors.justificatif" class="mt-1 text-sm text-[#A32D2D]">{{ form.errors.justificatif }}</p>
        </div>
        <div>
            <label class="text-[11px] font-bold uppercase tracking-wide text-[#888780]">Commentaire additionnel</label>
            <textarea
                v-model="form.commentaire"
                rows="3"
                class="mt-1 w-full rounded-md border border-[#e2e0d8] px-3 py-2 text-sm placeholder:text-[#888780]"
                placeholder="Précisions supplémentaires pour votre manager…"
            />
            <p v-if="form.errors.commentaire" class="mt-1 text-sm text-[#A32D2D]">{{ form.errors.commentaire }}</p>
        </div>
    </div>
</template>
