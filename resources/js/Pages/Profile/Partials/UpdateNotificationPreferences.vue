<script setup>
import { useForm, usePage } from '@inertiajs/vue3';
import { ref } from 'vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import Checkbox from '@/Components/Checkbox.vue';

const props = defineProps({
    preferences: {
        type: Array,
        required: true,
    },
});

const form = useForm({
    channels: {
        email: {
            enabled: props.preferences.find(p => p.channel === 'email')?.enabled ?? true,
            destination: props.preferences.find(p => p.channel === 'email')?.destination ?? usePage().props.auth.user.email,
        },
        sms: {
            enabled: props.preferences.find(p => p.channel === 'sms')?.enabled ?? false,
            destination: props.preferences.find(p => p.channel === 'sms')?.destination ?? '',
        },
        whatsapp: {
            enabled: props.preferences.find(p => p.channel === 'whatsapp')?.enabled ?? false,
            destination: props.preferences.find(p => p.channel === 'whatsapp')?.destination ?? '',
        },
    },
});

const updateNotifications = () => {
    form.post(route('notifications.update'), {
        preserveScroll: true,
        onSuccess: () => {
            // Toast or notification
        },
    });
};
</script>

<template>
    <section>
        <header>
            <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100">
                Notification Preferences
            </h2>

            <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                Manage how you receive alerts from your agents.
            </p>
        </header>

        <form @submit.prevent="updateNotifications" class="mt-6 space-y-6">
            
            <!-- Email -->
            <div class="space-y-2">
                <div class="flex items-center">
                    <Checkbox id="email_enabled" v-model:checked="form.channels.email.enabled" />
                    <InputLabel for="email_enabled" value="Enable Email Notifications" class="ml-2" />
                </div>
                <div v-if="form.channels.email.enabled" class="ml-6">
                     <InputLabel for="email_dest" value="Email Address" />
                     <TextInput id="email_dest" v-model="form.channels.email.destination" type="email" class="mt-1 block w-full" />
                </div>
            </div>

            <!-- SMS -->
            <div class="space-y-2 border-t border-gray-100 dark:border-gray-700 pt-4">
                <div class="flex items-center">
                    <Checkbox id="sms_enabled" v-model:checked="form.channels.sms.enabled" />
                    <InputLabel for="sms_enabled" value="Enable SMS Notifications" class="ml-2" />
                </div>
                <div v-if="form.channels.sms.enabled" class="ml-6">
                     <InputLabel for="sms_dest" value="Phone Number" />
                     <TextInput id="sms_dest" v-model="form.channels.sms.destination" type="tel" placeholder="+1234567890" class="mt-1 block w-full" />
                </div>
            </div>

            <div class="flex items-center gap-4">
                <PrimaryButton :disabled="form.processing">Save</PrimaryButton>

                <Transition
                    enter-active-class="transition ease-in-out"
                    enter-from-class="opacity-0"
                    leave-active-class="transition ease-in-out"
                    leave-to-class="opacity-0"
                >
                    <p v-if="form.recentlySuccessful" class="text-sm text-gray-600 dark:text-gray-400">Saved.</p>
                </Transition>
            </div>
        </form>
    </section>
</template>
