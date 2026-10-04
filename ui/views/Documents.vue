<script setup>
import { computed } from 'vue';
import { sdk, PageHeader, ResourceTable } from '../core';
import { contractsConfig, contractTemplatesConfig } from '../resources';
import ContractsAnalytics from './ContractsAnalytics.vue';

const props = defineProps( { tab: { type: String, default: 'all' }, query: { type: Object, default: () => ( {} ) } } );
const templates = computed( () => props.tab === 'templates' );
const analytics = computed( () => props.tab === 'analytics' );
const eyebrow = sdk.tp( 'contract' );
const desc = 'Agreements your ' + sdk.tp( 'client' ).toLowerCase() + ' sign online — with the proof to back them up.';
</script>

<template>
	<div class="bd-legal">
		<PageHeader v-if="analytics" :eyebrow="eyebrow" title="Analytics" description="How documents move: sent, signed, how long it takes, and where signers drop off." />
		<PageHeader v-else-if="!templates" :eyebrow="eyebrow" :title="'All ' + sdk.tp( 'contract' ).toLowerCase()" :description="desc" />
		<PageHeader v-else :eyebrow="eyebrow" title="Templates" :description="'Reusable ' + sdk.tp( 'contract' ).toLowerCase() + '. Insert fields like the ' + sdk.t( 'client' ).toLowerCase() + ' name and they fill in automatically.'" />
		<ContractsAnalytics v-if="analytics" />
		<ResourceTable v-else-if="!templates" :config="contractsConfig()" use-route-filters embedded />
		<ResourceTable v-else :config="contractTemplatesConfig()" embedded />
	</div>
</template>
