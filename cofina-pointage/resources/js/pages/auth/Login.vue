<script setup lang="ts">
import InputError from '@/components/InputError.vue';
import TextLink from '@/components/TextLink.vue';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import LoginLayout from '@/layouts/auth/LoginLayout.vue';
import { store } from '@/routes/login';
import { request } from '@/routes/password';
import { Form, Head } from '@inertiajs/vue3';
import {
    AlertCircle,
    CheckCircle2,
    Eye,
    EyeOff,
    LoaderCircle,
    Lock,
    Mail,
} from 'lucide-vue-next';
import { ref } from 'vue';

const props = defineProps<{
    status?: string;
    canResetPassword: boolean;
    canRegister: boolean;
    error?: string;
}>();

const showPassword = ref(false);
</script>

<template>
    <LoginLayout>
        <Head title="Connexion - COFINA Pointage" />

        <div
            v-if="status"
            class="mb-5 flex items-center gap-3 rounded-lg border border-green-200 bg-green-50 p-3 text-sm text-green-800"
        >
            <CheckCircle2 class="h-4 w-4 shrink-0 text-green-600" />
            <p class="font-medium">{{ status }}</p>
        </div>

        <div
            v-if="props.error"
            class="mb-5 flex items-start gap-3 rounded-lg border border-red-200 bg-red-50 p-3 text-sm text-red-800"
        >
            <AlertCircle class="mt-0.5 h-4 w-4 shrink-0 text-red-600" />
            <div>
                <p class="font-semibold">Accès refusé</p>
                <p class="mt-0.5 text-xs text-red-700/90">{{ props.error }}</p>
            </div>
        </div>

        <Form
            v-bind="store.form()"
            :reset-on-success="['password']"
            v-slot="{ errors, processing }"
            class="flex flex-col gap-4"
        >
            <div class="grid gap-1.5">
                <Label for="email">Adresse email</Label>
                <div class="relative">
                    <Mail class="absolute top-1/2 left-3 z-10 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
                    <Input
                        id="email"
                        type="email"
                        name="email"
                        required
                        autofocus
                        :tabindex="1"
                        autocomplete="email"
                        placeholder="nom.prenom@cofina.com"
                        class="h-11 pl-10"
                        :class="errors.email ? 'border-destructive' : ''"
                    />
                </div>
                <InputError :message="errors.email" />
            </div>

            <div class="grid gap-1.5">
                <Label for="password">Mot de passe</Label>
                <div class="relative">
                    <Lock class="absolute top-1/2 left-3 z-10 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
                    <Input
                        id="password"
                        :type="showPassword ? 'text' : 'password'"
                        name="password"
                        required
                        :tabindex="2"
                        autocomplete="current-password"
                        placeholder="••••••••"
                        class="h-11 pr-10 pl-10"
                        :class="errors.password ? 'border-destructive' : ''"
                    />
                    <button
                        type="button"
                        class="absolute top-1/2 right-3 -translate-y-1/2 text-muted-foreground hover:text-foreground"
                        :aria-label="showPassword ? 'Masquer le mot de passe' : 'Afficher le mot de passe'"
                        @click="showPassword = !showPassword"
                    >
                        <EyeOff v-if="showPassword" class="h-4 w-4" />
                        <Eye v-else class="h-4 w-4" />
                    </button>
                </div>
                <InputError :message="errors.password" />
            </div>

            <div class="flex items-center justify-between gap-3 pt-1">
                <Label for="remember" class="flex cursor-pointer items-center gap-2 text-sm font-normal">
                    <Checkbox id="remember" name="remember" :tabindex="3" />
                    <span>Se souvenir de moi</span>
                </Label>
                <TextLink
                    v-if="canResetPassword"
                    :href="request()"
                    class="text-sm text-primary hover:underline"
                    :tabindex="5"
                >
                    Mot de passe oublié ?
                </TextLink>
            </div>

            <Button type="submit" class="mt-2 h-11 w-full font-semibold" :tabindex="4" :disabled="processing" data-test="login-button">
                <LoaderCircle v-if="processing" class="mr-2 h-4 w-4 animate-spin" />
                {{ processing ? 'Connexion en cours...' : 'Se connecter' }}
            </Button>
        </Form>
    </LoginLayout>
</template>
