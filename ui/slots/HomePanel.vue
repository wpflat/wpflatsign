<script setup>
import { onMounted, ref } from 'vue';
import { api, navigate, sdk } from '../core';

/** Home: the documents that have waited longest for a signature. */
const props = defineProps( { home: { type: Object, default: null } } );
const items = ref( null );
onMounted( async () => {
	if ( ! props.home || props.home.stats.contracts_waiting === undefined ) return;
	try {
		items.value = ( await api.getPath( 'contracts/overview' ) ).awaiting.slice( 0, 5 );
	} catch ( e ) { items.value = []; }
} );
</script>

<template>
	<el-card v-if="items && items.length" shadow="never" class="bd-widget bd-mb">
		<template #header><div class="bd-widget__head"><span>{{ sdk.tp( 'contract' ) }} waiting for signature</span><a href="#/contracts/all?status=sent">See all</a></div></template>
		<ul class="bd-list">
			<li v-for="c in items" :key="c.id" class="bd-click" @click="navigate( `/contracts/${ c.id }` )">
				<div><div class="bd-strong">{{ c.title }}</div><div class="bd-muted bd-small">{{ [ c.number, c.client_name ].filter( Boolean ).join( ' · ' ) }}{{ c.waiting_on ? ' · waiting on ' + c.waiting_on : '' }}</div></div>
				<span class="bd-small bd-muted">{{ c.signer_done }}/{{ c.signer_total }} signed</span>
			</li>
		</ul>
	</el-card>
</template>
