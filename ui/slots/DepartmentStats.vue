<script setup>
import { StatCard, sdk } from '../core';

defineProps( { department: { type: Object, default: () => ( {} ) }, q: { type: Function, default: ( x ) => x } } );
</script>

<template>
	<el-col v-if="department.modules && department.modules.sign" :xs="12" :md="6">
		<StatCard :label="sdk.tp( 'contract' ) + ' waiting'" sub="Out for signature" :tone="department.modules.sign.waiting ? 'warning' : ''" :to="q( '/contracts/all?status=sent' )">{{ department.modules.sign.waiting }}</StatCard>
	</el-col>
</template>
