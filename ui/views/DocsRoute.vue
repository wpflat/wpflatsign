<script setup>
import { computed } from 'vue';
import Documents from './Documents.vue';
import ContractDetail from './ContractDetail.vue';

/** /contracts/:tab is a list tab (all, templates, analytics) — or a document: a number opens it, "new" starts one. PHP links use #/contracts/<id>. */
const props = defineProps( { tab: { type: String, default: 'all' }, query: { type: Object, default: () => ( {} ) } } );
const isDoc = computed( () => /^\d+$/.test( props.tab ) || props.tab === 'new' );
</script>

<template>
	<ContractDetail v-if="isDoc" :id="tab" :query="query" />
	<Documents v-else :tab="tab" :query="query" />
</template>
