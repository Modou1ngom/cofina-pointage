<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import PointageLayout from '@/layouts/pointage/PointageLayout.vue';
import type { BreadcrumbItem } from '@/types';

defineProps<{
    sites: number;
    sitesActifs: number;
    fraudes: number;
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Pointage', href: '/pointage' },
    { title: 'Admin IT', href: '#' },
];
</script>

<template>
    <PointageLayout title="Pointage — Admin IT" :breadcrumbs="breadcrumbs">
        <div class="mx-auto max-w-6xl space-y-6">
            <h1 class="text-xl font-semibold text-[#0C447C]">Tableau de bord — Administration pointage</h1>
            <div class="grid gap-3 sm:grid-cols-3">
                <div class="rounded-[10px] border border-[#e2e0d8] bg-white p-4">
                    <div class="text-[11px] font-semibold uppercase text-[#888780]">Sites</div>
                    <div class="mt-2 text-3xl font-medium">{{ sites }}</div>
                    <div class="text-xs text-[#888780]">{{ sitesActifs }} actifs</div>
                </div>
                <div class="rounded-[10px] border border-[#e2e0d8] bg-white p-4">
                    <div class="text-[11px] font-semibold uppercase text-[#888780]">Événements fraude (30j)</div>
                    <div class="mt-2 text-3xl font-medium text-[#A32D2D]">{{ fraudes }}</div>
                </div>
            </div>
            <div class="flex flex-wrap gap-3">
                <Link href="/pointage/sites" class="rounded-md bg-[#185FA5] px-4 py-2 text-sm text-white">Sites & agences</Link>
                <Link href="/pointage/admin/qrcodes" class="rounded-md border border-[#e2e0d8] bg-white px-4 py-2 text-sm">QR Codes</Link>
                <Link href="/pointage/admin/logs" class="rounded-md border border-[#e2e0d8] bg-white px-4 py-2 text-sm">Logs</Link>
                <Link href="/pointage/admin/securite" class="rounded-md border border-[#e2e0d8] bg-white px-4 py-2 text-sm">Sécurité</Link>
            </div>
        </div>
    </PointageLayout>
</template>
