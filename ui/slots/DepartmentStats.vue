<script setup>
import { StatCard, sdk } from '../core';

defineProps( { department: { type: Object, default: () => ( {} ) }, q: { type: Function, default: ( x ) => x } } );
</script>

<template>
	<el-col v-if="department.modules && department.modules.doc" :xs="12" :md="6">
		<StatCard :label="sdk.tp( 'contract' ) + ' waiting'" sub="Out for signature" :tone="department.modules.doc.waiting ? 'warning' : ''" :to="q( '/contracts/all?status=sent' )">{{ department.modules.doc.waiting }}</StatCard>
	</el-col>
</template>
