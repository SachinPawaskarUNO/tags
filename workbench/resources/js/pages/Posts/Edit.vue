<script setup>
import { useForm } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import TagInputAdvanced from '@/vendor/tags/components/advanced/TagInputAdvanced.vue';

// Set up like the README's advanced mode section.
const props = defineProps(['post', 'suggestions', 'details', 'models', 'model', 'icons']);

const form = useForm({
    tags: props.post.tags.map((tag) => tag.name),
    colors: Object.fromEntries(props.post.tags.map((tag) => [tag.name, tag.color])),
    icons: Object.fromEntries(props.post.tags.map((tag) => [tag.name, tag.icon])),
    descriptions: Object.fromEntries(props.post.tags.map((tag) => [tag.name, tag.description])),
});

const save = () => form.put(`/posts/${props.post.id}`);
</script>

<template>
    <AppLayout>
        <h1 class="mb-4 text-xl font-semibold">{{ post.title }}</h1>
        <form class="space-y-4" @submit.prevent="save">
            <TagInputAdvanced
                v-model="form.tags"
                v-model:colors="form.colors"
                v-model:icons="form.icons"
                v-model:descriptions="form.descriptions"
                :suggestions="suggestions"
                :details="details"
                :models="models"
                :model="model"
                :available-icons="icons"
            />
            <ul v-if="Object.keys(form.errors).length > 0" class="text-sm text-red-600">
                <li v-for="(error, field) in form.errors" :key="field">{{ error }}</li>
            </ul>
            <button type="submit" class="rounded border px-3 py-1 text-sm" :disabled="form.processing">Save</button>
        </form>
    </AppLayout>
</template>
