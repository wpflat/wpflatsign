<script setup>
import { sdk } from '../core';

defineProps( { card: { type: Object, default: () => ( {} ) } } );
</script>

<template>
	<span v-if="card.modules && card.modules.sign"><b>{{ card.modules.sign.waiting }}</b> {{ sdk.tp( 'contract' ).toLowerCase() }} waiting</span>
</template>
