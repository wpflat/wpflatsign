<script setup>
import { sdk } from '../core';

defineProps( { card: { type: Object, default: () => ( {} ) } } );
</script>

<template>
	<span v-if="card.modules && card.modules.doc"><b>{{ card.modules.doc.waiting }}</b> {{ sdk.tp( 'contract' ).toLowerCase() }} waiting</span>
</template>
