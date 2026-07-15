<script setup lang="ts">
import PointageLayout from '@/layouts/pointage/PointageLayout.vue';
import type { BreadcrumbItem } from '@/types';

defineProps<{
    resume: { total: number; retards: number; arrivees: number; departs: number };
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Pointage', href: '/pointage' },
    { title: 'Rapports service', href: '#' },
];
</script>

<template>
    <PointageLayout title="Rapports service" :breadcrumbs="breadcrumbs">
        <div class="mx-auto max-w-3xl space-y-6">
            <h1 class="text-xl font-semibold text-[#0C447C]">Synthèse du mois (service)</h1>
            <div class="grid gap-3 sm:grid-cols-2">
                <div class="rounded-[10px] border border-[#e2e0d8] bg-white p-4">
                    <div class="text-[11px] font-semibold uppercase text-[#888780]">Total pointages</div>
                    <div class="mt-2 text-2xl font-medium">{{ resume.total }}</div>
                </div>
                <div class="rounded-[10px] border border-[#e2e0d8] bg-white p-4">
                    <div class="text-[11px] font-semibold uppercase text-[#888780]">Retards</div>
                    <div class="mt-2 text-2xl font-medium">{{ resume.retards }}</div>
                </div>
                <div class="rounded-[10px] border border-[#e2e0d8] bg-white p-4">
                    <div class="text-[11px] font-semibold uppercase text-[#888780]">Arrivées</div>
                    <div class="mt-2 text-2xl font-medium">{{ resume.arrivees }}</div>
                </div>
                <div class="rounded-[10px] border border-[#e2e0d8] bg-white p-4">
                    <div class="text-[11px] font-semibold uppercase text-[#888780]">Départs</div>
                    <div class="mt-2 text-2xl font-medium">{{ resume.departs }}</div>
                </div>
            </div>
        </div>
    </PointageLayout>
</template>
