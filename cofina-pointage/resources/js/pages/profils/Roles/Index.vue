<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Button } from '@/components/ui/button';
import DataTable, { type Column } from '@/components/DataTable.vue';
import { Code, Eye, Pencil, Trash2 } from 'lucide-vue-next';
import { computed } from 'vue';

interface Role {
    id: number;
    nom: string;
    slug: string;
    description?: string;
    actif: boolean;
}

interface Props {
    roles: Role[];
}

const props = defineProps<Props>();

const getStatusBadge = (actif: boolean) => {
    if (actif) {
        return 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200';
    }
    return 'bg-gray-100 text-gray-800 dark:bg-gray-800 dark:text-gray-200';
};

const getStatusLabel = (actif: boolean) => {
    return actif ? 'Actif' : 'Inactif';
};

const columns: Column[] = [
    {
        key: 'nom',
        title: 'NAME',
        sortable: true,
    },
    {
        key: 'description',
        title: 'DESCRIPTION',
    },
    {
        key: 'actif',
        title: 'STATUS',
    },
    {
        key: 'actions',
        title: 'ACTIONS',
    },
];

const tableData = computed(() => {
    return props.roles.map(role => ({
        id: role.id,
        nom: role.nom,
        description: role.description || '-',
        actif: role.actif,
        role: role,
    }));
});

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Rôles',
        href: '#',
    },
];

const deleteRole = (id: number) => {
    if (confirm('Êtes-vous sûr de vouloir supprimer ce rôle ?')) {
        router.delete(`/roles/${id}`);
    }
};
</script>

<template>
    <Head title="Rôles" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-col gap-6 p-6">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <h1 class="text-3xl font-bold text-gray-900">Liste des rôles</h1>
                    <Code class="h-5 w-5 text-gray-500" />
                </div>
                <div class="flex gap-2">
                    <Link href="/profils">
                        <Button variant="outline">Retour aux profils</Button>
                    </Link>
                    <Link href="/roles/create">
                        <Button>Nouveau rôle</Button>
                    </Link>
                </div>
            </div>

            <DataTable
                :headers="columns"
                :items="tableData"
                :current-page="1"
                :items-per-page="5"
                :total-items="tableData.length"
                show-select
            >
                <template #item.nom="{ item }">
                    <span class="text-gray-900 font-medium">{{ item.nom }}</span>
                </template>

                <template #item.description="{ item }">
                    <span class="text-gray-900">{{ item.description }}</span>
                </template>

                <template #item.actif="{ item }">
                    <span
                        :class="[
                            'rounded-full px-3 py-1 text-xs font-medium',
                            getStatusBadge(item.actif),
                        ]"
                    >
                        {{ getStatusLabel(item.actif) }}
                    </span>
                </template>

                <template #item.actions="{ item }">
                    <div class="flex items-center gap-1">
                        <Link
                            :href="`/roles/${item.id}`"
                            class="inline-flex items-center justify-center rounded-md p-2 text-gray-600 hover:bg-gray-100 hover:text-gray-900 transition-colors"
                            title="Voir"
                        >
                            <Eye class="h-5 w-5" />
                        </Link>
                        <Link
                            :href="`/roles/${item.id}/edit`"
                            class="inline-flex items-center justify-center rounded-md p-2 text-gray-600 hover:bg-gray-100 hover:text-gray-900 transition-colors"
                            title="Modifier"
                        >
                            <Pencil class="h-5 w-5" />
                        </Link>
                        <button
                            @click="deleteRole(item.id)"
                            class="inline-flex items-center justify-center rounded-md p-2 text-red-600 hover:bg-red-50 hover:text-red-700 transition-colors"
                            title="Supprimer"
                        >
                            <Trash2 class="h-5 w-5" />
                        </button>
                    </div>
                </template>
            </DataTable>
        </div>
    </AppLayout>
</template>

