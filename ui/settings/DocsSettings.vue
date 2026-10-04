<script setup>
import { onMounted, reactive, ref } from 'vue';
import { api, errorMessage, store, sdk, ElMessage } from '../core';

/** The Docs tab of Settings: who can open a preview link by default. */
const form = reactive( { contract_view_default: 'private' } );
const loaded = ref( false );
const saving = ref( false );

const load = async () => {
	try {
		Object.assign( form, await api.getPath( 'doc/settings' ) );
		loaded.value = true;
	} catch ( e ) {
		ElMessage.error( errorMessage( e ) );
	}
};
onMounted( load );

const save = async () => {
	saving.value = true;
	try {
		Object.assign( form, await api.post( 'doc/settings', { ...form } ) );
		store.meta = await api.getPath( 'meta' );
		ElMessage.success( 'Document settings saved' );
	} catch ( e ) {
		ElMessage.error( errorMessage( e ) );
	} finally {
		saving.value = false;
	}
};
</script>

<template>
	<div v-loading="!loaded" class="bd-docsettings">
		<el-card shadow="never" class="bd-mb">
			<template #header><div class="bd-widget__head"><span>{{ sdk.tp( 'contract' ) }}</span></div></template>
			<el-form label-position="top" style="max-width: 640px" @submit.prevent>
				<el-form-item label="Who can open the preview link">
					<el-radio-group v-model="form.contract_view_default">
						<el-radio-button value="private">Signers only</el-radio-button>
						<el-radio-button value="public">Anyone with the link</el-radio-button>
					</el-radio-group>
					<div class="bd-help">The default for new {{ sdk.tp( 'contract' ).toLowerCase() }}. You can change it on each one.</div>
				</el-form-item>
			</el-form>
		</el-card>
		<el-button type="primary" :loading="saving" @click="save">Save settings</el-button>
	</div>
</template>
