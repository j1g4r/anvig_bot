<script setup>
defineOptions({
    inheritAttrs: false
});
</script>

<template>
    <img 
        src="/images/logo.png" 
        alt="ANVIG Logo" 
        v-bind="$attrs"
        :class="[$attrs.class, 'object-contain']"
        style="max-height: 48px; width: auto; height: auto;"
    />
</template>

