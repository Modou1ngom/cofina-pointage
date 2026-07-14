<script setup lang="ts">
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AuthBase from '@/layouts/AuthLayout.vue';
import { Form, Head, useForm } from '@inertiajs/vue3';
import { LoaderCircle, Lock, AlertCircle, Shield } from 'lucide-vue-next';

const form = useForm({
    password: '',
    password_confirmation: '',
});

const submit = () => {
    form.post('/password/change', {
        onSuccess: () => {
            // Redirection gérée par le contrôleur
        },
        onFinish: () => {
            form.reset('password', 'password_confirmation');
        },
    });
};
</script>

<template>
    <AuthBase
        title="Nouveau mot de passe"
        description="Pour des raisons de sécurité, définissez un nouveau mot de passe avant d'accéder à COFINA Pointage."
    >
        <Head title="Changement de mot de passe - COFINA Pointage" />

        <div
            class="mb-6 flex items-start gap-3 rounded-lg border border-amber-200 bg-amber-50 p-4 text-sm text-amber-900"
        >
            <Shield class="mt-0.5 h-5 w-5 shrink-0 text-amber-600" />
            <div class="flex-1">
                <p class="mb-1 font-semibold">Changement de mot de passe requis</p>
                <p class="text-xs leading-relaxed text-amber-800/90">
                    Vous devez modifier votre mot de passe avant de continuer.
                </p>
            </div>
        </div>

        <Form
            @submit.prevent="submit"
            v-slot="{ errors, processing }"
            class="flex flex-col gap-5"
        >
            <!-- Champ Nouveau mot de passe -->
            <div class="grid gap-2">
                <Label for="password">Nouveau mot de passe</Label>
                <div class="relative">
                    <Lock class="absolute top-1/2 left-3.5 z-10 h-5 w-5 -translate-y-1/2 text-muted-foreground" />
                    <Input
                        id="password"
                        type="password"
                        v-model="form.password"
                        required
                        autofocus
                        autocomplete="new-password"
                        placeholder="Nouveau mot de passe"
                        class="h-12 pl-11"
                    />
                </div>
                <InputError :message="errors.password" />
            </div>

            <!-- Champ Confirmation du nouveau mot de passe -->
            <div class="grid gap-2">
                <Label for="password_confirmation">Confirmer le nouveau mot de passe</Label>
                <div class="relative">
                    <Lock class="absolute top-1/2 left-3.5 z-10 h-5 w-5 -translate-y-1/2 text-muted-foreground" />
                    <Input
                        id="password_confirmation"
                        type="password"
                        v-model="form.password_confirmation"
                        required
                        autocomplete="new-password"
                        placeholder="Confirmer le nouveau mot de passe"
                        class="h-12 pl-11"
                    />
                </div>
                <InputError :message="errors.password_confirmation" />
            </div>

            <!-- Bouton de soumission -->
            <Button type="submit" class="mt-2 h-12 w-full font-semibold" :disabled="processing">
                <LoaderCircle
                    v-if="processing"
                    class="mr-2 h-5 w-5 animate-spin"
                />
                <span v-if="processing">Modification en cours...</span>
                <span v-else>Modifier le mot de passe</span>
            </Button>
        </Form>
    </AuthBase>
</template>
