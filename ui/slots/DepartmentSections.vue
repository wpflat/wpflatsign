<script setup>
import { ChartCard, fmtDate, navigate, sdk } from '../core';
import { statusOf } from '../util';

defineProps( { department: { type: Object, default: () => ( {} ) }, q: { type: Function, default: ( x ) => x } } );
</script>

<template>
	<div v-if="department.modules && department.modules.docs" class="bd-angrid bd-mt">
		<ChartCard :title="sdk.tp( 'contract' )" sub="Most recent" :empty="!department.modules.docs.recent.length" :empty-text="'No ' + sdk.tp( 'contract' ).toLowerCase() + ' yet.'">
			<template #tools><a class="bd-link bd-small" :href="'#' + q( '/contracts/all' )" @click.prevent="navigate( q( '/contracts/all' ) )">See all</a></template>
			<el-table :data="department.modules.docs.recent" size="small">
				<el-table-column :label="sdk.t( 'contract' )" min-width="190"><template #default="{ row }"><a class="bd-link" :href="'#/contracts/' + row.id">{{ row.title }}</a><div class="bd-muted bd-small">{{ [ row.number, row.client_name ].filter( Boolean ).join( ' · ' ) }}</div></template></el-table-column>
				<el-table-column label="Status" min-width="150"><template #default="{ row }"><span :class="[ 'bd-badge', 'bd-badge--' + statusOf( row.status ).tone ]">{{ statusOf( row.status ).label }}</span></template></el-table-column>
				<el-table-column label="Sent" min-width="100" align="right"><template #default="{ row }">{{ row.sent_at ? fmtDate( row.sent_at ) : '—' }}</template></el-table-column>
			</el-table>
		</ChartCard>
	</div>
</template>
