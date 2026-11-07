<script setup>
import AppLayout from '@/layouts/AppLayout.vue';
import TagManagerPage from '@/vendor/tags/components/advanced/TagManagerPage.vue';

defineProps(['tags', 'existing', 'models', 'icons', 'storeUrl', 'maxDescriptionLength']);
</script>

<template>
    <AppLayout>
        <TagManagerPage
            :tags="tags"
            :existing="existing"
            :models="models"
            :icons="icons"
            :store-url="storeUrl"
            :max-description-length="maxDescriptionLength"
        />
    </AppLayout>
</template>
