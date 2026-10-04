<script setup>
import { onMounted, ref, watch } from 'vue';
import { api, fmtDate, sdk } from '../core';
import { statusOf, WAITING } from '../util';

const props = defineProps( { clientId: { type: Number, required: true }, tick: { type: Number, default: 0 }, goTab: { type: Function, default: () => {} } } );
const items = ref( [] );
const load = async () => {
	try {
		const res = await api.list( 'contracts', { client_id: props.clientId, per_page: 50 } );
		items.value = res.items.filter( ( c ) => WAITING.includes( c.status ) ).slice( 0, 6 );
	} catch ( e ) { items.value = []; }
};
onMounted( load );
watch( () => props.tick, load );
</script>

<template>
	<el-card shadow="never" class="bd-widget">
		<template #header><div class="bd-widget__head"><span>Waiting for signature</span><a href="#" @click.prevent="goTab( 'docs' )">Open {{ sdk.tp( 'contract' ).toLowerCase() }}</a></div></template>
		<ul class="bd-list">
			<li v-for="c in items" :key="c.id">
				<div><a class="bd-strong bd-link" :href="'#/contracts/' + c.id">{{ c.title }}</a><div class="bd-muted bd-small">{{ [ c.number, statusOf( c.status ).label, c.signer_done + '/' + c.signer_total + ' signed' ].join( ' · ' ) }}</div></div>
				<span class="bd-small bd-muted">{{ c.sent_at ? fmtDate( c.sent_at ) : '' }}</span>
			</li>
			<li v-if="!items.length" class="bd-muted">Nothing is waiting for a signature.</li>
		</ul>
	</el-card>
</template>
