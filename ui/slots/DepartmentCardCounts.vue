<script setup>
import { sdk } from '../core';

defineProps( { card: { type: Object, default: () => ( {} ) } } );
</script>

<template>
	<span v-if="card.modules && card.modules.docs"><b>{{ card.modules.docs.waiting }}</b> {{ sdk.tp( 'contract' ).toLowerCase() }} waiting</span>
</template>
