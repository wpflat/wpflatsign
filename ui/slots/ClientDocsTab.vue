<script setup>
import { computed } from 'vue';
import { sdk, ResourceTable } from '../core';
import { contractsConfig } from '../resources';

const props = defineProps( { clientId: { type: Number, required: true }, tick: { type: Number, default: 0 }, refresh: { type: Function, default: () => {} } } );
const fixed = computed( () => ( { client_id: props.clientId } ) );
const config = computed( () => contractsConfig( { fixed: fixed.value } ) );
</script>

<template>
	<div>
		<h3 class="bd-subhead" style="margin-top: 4px">{{ sdk.tp( 'contract' ) }}</h3>
		<ResourceTable :config="config" :fixed="fixed" embedded :reload-key="tick" @changed="refresh" />
	</div>
</template>
