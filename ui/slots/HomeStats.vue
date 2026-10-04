<script setup>
import { DocumentChecked } from '@element-plus/icons-vue';
import { StatCard, sdk } from '../core';

defineProps( { home: { type: Object, default: null } } );
</script>

<template>
	<el-col v-if="home && home.stats.contracts_waiting !== undefined" :xs="12" :md="6">
		<StatCard :label="sdk.tp( 'contract' ) + ' waiting'" :icon="DocumentChecked" :tone="home.stats.contracts_waiting ? 'warning' : ''" sub="Waiting for signatures" to="/contracts/all?status=sent"><span class="bd-stat__text">{{ home.stats.contracts_waiting }}</span></StatCard>
	</el-col>
</template>
