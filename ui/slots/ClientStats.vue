<script setup>
import { onMounted, reactive, watch } from 'vue';
import { DocumentChecked } from '@element-plus/icons-vue';
import { api, StatCard, sdk } from '../core';
import { WAITING } from '../util';

const props = defineProps( { clientId: { type: Number, required: true }, tick: { type: Number, default: 0 } } );
const d = reactive( { waiting: 0, signed: 0, total: 0 } );
const load = async () => {
	try {
		const res = await api.list( 'contracts', { client_id: props.clientId, per_page: 200 } );
		d.total = res.total;
		d.waiting = res.items.filter( ( c ) => WAITING.includes( c.status ) ).length;
		d.signed = res.items.filter( ( c ) => c.status === 'signed' ).length;
	} catch ( e ) { /* the card stays at 0 */ }
};
onMounted( load );
watch( () => props.tick, load );
</script>

<template>
	<el-col :xs="12" :md="6"><StatCard label="Waiting for signature" :icon="DocumentChecked" :tone="d.waiting ? 'warning' : ''" :sub="d.signed + ' signed · ' + d.total + ' ' + sdk.tp( 'contract' ).toLowerCase()"><span class="bd-stat__text">{{ d.waiting }}</span></StatCard></el-col>
</template>
