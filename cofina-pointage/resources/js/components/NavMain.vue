<script setup lang="ts">
import {
    Collapsible,
    CollapsibleContent,
    CollapsibleTrigger,
} from '@/components/ui/collapsible';
import {
    SidebarGroup,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
    SidebarMenuSub,
    SidebarMenuSubButton,
    SidebarMenuSubItem,
} from '@/components/ui/sidebar';
import { urlIsActive } from '@/lib/utils';
import { type NavItem } from '@/types';
import { Link, usePage } from '@inertiajs/vue3';
import { ChevronRight } from 'lucide-vue-next';

defineProps<{
    items: NavItem[];
}>();

const page = usePage();

/** Actif si l’élément ou l’un de ses descendants correspond à l’URL courante. */
function navItemActive(item: NavItem): boolean {
    if (item.variant === 'section-label') {
        return false;
    }
    if (item.href) {
        return urlIsActive(item.href, page.url);
    }
    if (item.items?.length) {
        return item.items.some((child) => navItemActive(child));
    }

    return false;
}

function isLeafHrefActive(href?: string): boolean {
    if (!href) {
        return false;
    }
    return urlIsActive(href, page.url);
}
</script>

<template>
    <SidebarGroup class="px-2 py-0">
        <SidebarMenu>
            <SidebarMenuItem v-for="item in items" :key="item.title">
                <!-- Menu avec sous-menus -->
                <Collapsible v-if="item.items && item.items.length > 0" :default-open="navItemActive(item)">
                    <template #default="{ open }">
                        <CollapsibleTrigger as-child>
                            <SidebarMenuButton
                                :is-active="navItemActive(item)"
                                :tooltip="item.title"
                            >
                                <component :is="item.icon" />
                                <span>{{ item.title }}</span>
                                <ChevronRight class="ml-auto size-5 transition-transform duration-200" :class="{ 'rotate-90': open }" />
                            </SidebarMenuButton>
                        </CollapsibleTrigger>
                        <CollapsibleContent>
                            <SidebarMenuSub>
                                <SidebarMenuSubItem
                                    v-for="(subItem, subIdx) in item.items"
                                    :key="`${subItem.title}-${subIdx}`"
                                >
                                    <div
                                        v-if="subItem.variant === 'section-label'"
                                        class="px-2 pb-1 pt-3 text-[10px] font-bold uppercase tracking-wider text-muted-foreground"
                                    >
                                        {{ subItem.title }}
                                    </div>
                                    <!-- Sous-groupe imbriqué (ex. Configuration) -->
                                    <Collapsible
                                        v-else-if="subItem.items?.length"
                                        class="group/coll2 w-full"
                                        :default-open="navItemActive(subItem)"
                                    >
                                        <CollapsibleTrigger as-child>
                                            <SidebarMenuSubButton
                                                class="w-full"
                                                :is-active="navItemActive(subItem)"
                                            >
                                                <component
                                                    :is="subItem.icon"
                                                    v-if="subItem.icon"
                                                    class="size-4 shrink-0 opacity-80"
                                                />
                                                <ChevronRight
                                                    class="size-4 shrink-0 opacity-70 transition-transform duration-200 group-data-[state=open]/coll2:rotate-90"
                                                />
                                                <span
                                                    class="min-w-0 flex-1 truncate text-left"
                                                    :title="subItem.title"
                                                >{{ subItem.title }}</span>
                                            </SidebarMenuSubButton>
                                        </CollapsibleTrigger>
                                        <CollapsibleContent>
                                            <div class="ml-1 space-y-0.5 border-l border-sidebar-border py-1 pl-2">
                                                <template
                                                    v-for="(nested, nIdx) in subItem.items"
                                                    :key="`${nested.title}-${nIdx}`"
                                                >
                                                    <!-- Niveau 3 : sous-groupe sans lien direct (ex. Jour ouvrable) -->
                                                    <Collapsible
                                                        v-if="nested.items?.length && !nested.href"
                                                        class="group/coll3 w-full"
                                                        :default-open="navItemActive(nested)"
                                                    >
                                                        <CollapsibleTrigger as-child>
                                                            <SidebarMenuSubButton
                                                                class="w-full"
                                                                :is-active="navItemActive(nested)"
                                                            >
                                                                <component
                                                                    :is="nested.icon"
                                                                    v-if="nested.icon"
                                                                    class="size-4 shrink-0 opacity-80"
                                                                />
                                                                <ChevronRight
                                                                    class="size-4 shrink-0 opacity-70 transition-transform duration-200 group-data-[state=open]/coll3:rotate-90"
                                                                />
                                                                <span
                                                                    class="min-w-0 flex-1 truncate text-left"
                                                                    :title="nested.title"
                                                                >{{ nested.title }}</span>
                                                            </SidebarMenuSubButton>
                                                        </CollapsibleTrigger>
                                                        <CollapsibleContent>
                                                            <div class="ml-1 space-y-0.5 border-l border-sidebar-border py-1 pl-2">
                                                                <SidebarMenuSubButton
                                                                    v-for="(leaf, leafIdx) in nested.items"
                                                                    :key="`${leaf.title}-${leafIdx}`"
                                                                    as-child
                                                                    :is-active="isLeafHrefActive(leaf.href)"
                                                                >
                                                                    <Link
                                                                        v-if="leaf.href"
                                                                        :href="leaf.href"
                                                                        class="flex w-full items-center gap-2"
                                                                    >
                                                                        <component
                                                                            :is="leaf.icon"
                                                                            v-if="leaf.icon"
                                                                            class="size-4 shrink-0 opacity-80"
                                                                            :class="leaf.iconClass"
                                                                        />
                                                                        <span
                                                                            class="min-w-0 flex-1 truncate"
                                                                            :title="leaf.title"
                                                                        >{{ leaf.title }}</span>
                                                                    </Link>
                                                                </SidebarMenuSubButton>
                                                            </div>
                                                        </CollapsibleContent>
                                                    </Collapsible>
                                                    <SidebarMenuSubButton
                                                        v-else-if="nested.href"
                                                        as-child
                                                        :is-active="isLeafHrefActive(nested.href)"
                                                    >
                                                        <Link
                                                            :href="nested.href"
                                                            class="flex w-full items-center gap-2"
                                                        >
                                                            <component
                                                                :is="nested.icon"
                                                                v-if="nested.icon"
                                                                class="size-4 shrink-0 opacity-80"
                                                                :class="nested.iconClass"
                                                            />
                                                            <span
                                                                class="min-w-0 flex-1 truncate"
                                                                :title="nested.title"
                                                            >{{ nested.title }}</span>
                                                        </Link>
                                                    </SidebarMenuSubButton>
                                                </template>
                                            </div>
                                        </CollapsibleContent>
                                    </Collapsible>
                                    <SidebarMenuSubButton
                                        v-else-if="subItem.href"
                                        as-child
                                        :is-active="isLeafHrefActive(subItem.href)"
                                    >
                                        <Link
                                            :href="subItem.href"
                                            class="flex w-full items-center gap-2"
                                        >
                                            <component
                                                :is="subItem.icon"
                                                v-if="subItem.icon"
                                                class="size-4 shrink-0 opacity-80"
                                                :class="subItem.iconClass"
                                            />
                                            <span
                                                class="min-w-0 flex-1 truncate"
                                                :title="subItem.title"
                                            >{{ subItem.title }}</span>
                                            <span
                                                v-if="subItem.badge != null && subItem.badge > 0"
                                                class="flex size-5 shrink-0 items-center justify-center rounded-full bg-[#DC2626] text-[10px] font-bold text-white"
                                            >
                                                {{ subItem.badge > 9 ? '9+' : subItem.badge }}
                                            </span>
                                        </Link>
                                    </SidebarMenuSubButton>
                                    <SidebarMenuSubButton
                                        v-else-if="subItem.onClick"
                                        :is-active="false"
                                        @click="subItem.onClick"
                                    >
                                        <span>{{ subItem.title }}</span>
                                    </SidebarMenuSubButton>
                                    <SidebarMenuSubButton
                                        v-else
                                        :is-active="false"
                                        disabled
                                    >
                                        <span>{{ subItem.title }}</span>
                                    </SidebarMenuSubButton>
                                </SidebarMenuSubItem>
                            </SidebarMenuSub>
                        </CollapsibleContent>
                    </template>
                </Collapsible>
                <!-- Menu simple sans sous-menus -->
                <SidebarMenuButton
                    v-else
                    as-child
                    :is-active="urlIsActive(item.href!, page.url)"
                    :tooltip="item.title"
                >
                    <Link :href="item.href">
                        <component :is="item.icon" />
                        <span>{{ item.title }}</span>
                    </Link>
                </SidebarMenuButton>
            </SidebarMenuItem>
        </SidebarMenu>
    </SidebarGroup>
</template>
