import { FileSignature } from 'lucide-vue-next';
import { sdk } from './core';
import { canDocs } from './util';
import ContractsOverview from './views/ContractsOverview.vue';
import DocsRoute from './views/DocsRoute.vue';
import RichText from './components/RichText.vue';
import ClientDocsTab from './slots/ClientDocsTab.vue';
import ClientStats from './slots/ClientStats.vue';
import ClientOverview from './slots/ClientOverview.vue';
import DepartmentStats from './slots/DepartmentStats.vue';
import DepartmentSections from './slots/DepartmentSections.vue';
import DepartmentCardCounts from './slots/DepartmentCardCounts.vue';
import HomeStats from './slots/HomeStats.vue';
import HomePanel from './slots/HomePanel.vue';
import DocsSettings from './settings/DocsSettings.vue';

/** BizyDocs registers its screens and what it adds to BizyCore's pages. Nothing runs unless the module is on. */
const M = 'docs';
sdk.registerIcon( 'docs', FileSignature );
sdk.registerFieldType( 'richtext', RichText );

sdk.registerRoute( { path: '/contracts', module: M, component: ContractsOverview } );
// One two-part route: a list tab (all, templates, analytics) or a document. PHP links and notifications use #/contracts/<id>; "new" starts a draft.
sdk.registerRoute( { path: '/contracts/:tab', module: M, component: DocsRoute } );

sdk.registerSlot( 'client.tabs', { id: 'docs', module: M, order: 40, label: sdk.tp( 'contract' ), component: ClientDocsTab, when: canDocs } );
sdk.registerSlot( 'client.stats', { id: 'docs', module: M, order: 40, component: ClientStats, when: canDocs } );
sdk.registerSlot( 'client.overview', { id: 'docs', module: M, order: 40, component: ClientOverview, when: canDocs } );
sdk.registerSlot( 'department.stats', { id: 'docs', module: M, order: 40, component: DepartmentStats, when: canDocs } );
sdk.registerSlot( 'department.sections', { id: 'docs', module: M, order: 40, component: DepartmentSections, when: canDocs } );
sdk.registerSlot( 'department.card.counts', { id: 'docs', module: M, order: 40, component: DepartmentCardCounts, when: canDocs } );
sdk.registerSlot( 'home.stats', { id: 'docs', module: M, order: 40, component: HomeStats, when: canDocs } );
sdk.registerSlot( 'home.panels', { id: 'docs', module: M, order: 40, component: HomePanel, when: canDocs } );
sdk.registerSlot( 'settings.tabs', { id: 'docs', module: M, order: 40, label: 'Docs', component: DocsSettings, when: canDocs } );

// Lists the owner can edit under Settings → Lists & options.
sdk.extend( 'lookup.types', { id: 'doc_type', module: M, order: 70, label: 'Document types' } );

// What shows in the Activity list.
sdk.extend( 'activity.types', { id: 'docs-contracts', module: M, order: 70, type: { value: 'contracts', label: sdk.tp( 'contract' ) } } );
sdk.extend( 'activity.types', { id: 'docs-templates', module: M, order: 71, type: { value: 'contract-templates', label: 'Templates' } } );
