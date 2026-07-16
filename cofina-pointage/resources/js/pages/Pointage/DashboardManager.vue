<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import PointageLayout from '@/layouts/pointage/PointageLayout.vue';
import type { BreadcrumbItem } from '@/types';

defineProps<{
    pendingValidations: number;
    pointagesTodayDistinctUsers: number;
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Pointage', href: '/pointage' },
    { title: 'Manager', href: '#' },
];
</script>

<template>
    <PointageLayout title="Pointage — Manager" :breadcrumbs="breadcrumbs">
        <div class="mx-auto max-w-6xl space-y-6">
            <h1 class="text-xl font-semibold text-[#0C447C]">Tableau de bord — Manager</h1>
            <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                <div class="rounded-[10px] border border-[#e2e0d8] bg-white p-4">
                    <div class="text-[11px] font-semibold uppercase text-[#888780]">Validations en attente</div>
                    <div class="mt-2 text-3xl font-medium">{{ pendingValidations }}</div>
                    <Link href="/pointage/declarations/validation-manager" class="mt-3 inline-block text-sm text-[#185FA5] underline"
                        >Traiter</Link
                    >
                </div>
                <div class="rounded-[10px] border border-[#e2e0d8] bg-white p-4">
                    <div class="text-[11px] font-semibold uppercase text-[#888780]">Pointages (employés distincts aujourd'hui)</div>
                    <div class="mt-2 text-3xl font-medium">{{ pointagesTodayDistinctUsers }}</div>
                </div>
            </div>
            <div class="flex flex-wrap gap-3">
                <Link href="/pointage/equipe" class="rounded-md border border-[#e2e0d8] bg-white px-4 py-2 text-sm hover:bg-[#FAFAF8]"
                    >Mon équipe</Link
                >
                <Link href="/pointage/retards-absences" class="rounded-md border border-[#e2e0d8] bg-white px-4 py-2 text-sm hover:bg-[#FAFAF8]"
                    >Retards & absences</Link
                >
                <Link href="/pointage/rapports-service" class="rounded-md border border-[#e2e0d8] bg-white px-4 py-2 text-sm hover:bg-[#FAFAF8]"
                    >Rapports service</Link
                >
            </div>
        </div>
    </PointageLayout>
</template>
