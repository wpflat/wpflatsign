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

/** WPFlat Sign registers its screens and what it adds to WPFlat Core's pages. Nothing runs unless the module is on. */
const M = 'sign';
sdk.registerIcon( 'sign', FileSignature );
sdk.registerFieldType( 'richtext', RichText );

sdk.registerQuickAdd?.( M, [ { label: 'New document', route: '/contracts/all?new=1' } ] );
sdk.registerRoute( { path: '/contracts', module: M, component: ContractsOverview } );
// One two-part route: a list tab (all, templates, analytics) or a document. PHP links and notifications use #/contracts/<id>; "new" starts a draft.
sdk.registerRoute( { path: '/contracts/:tab', module: M, component: DocsRoute } );

sdk.registerSlot( 'client.tabs', { id: 'sign', module: M, order: 40, label: sdk.tp( 'contract' ), component: ClientDocsTab, when: canDocs } );
sdk.registerSlot( 'client.stats', { id: 'sign', module: M, order: 40, component: ClientStats, when: canDocs } );
sdk.registerSlot( 'client.overview', { id: 'sign', module: M, order: 40, component: ClientOverview, when: canDocs } );
sdk.registerSlot( 'department.stats', { id: 'sign', module: M, order: 40, component: DepartmentStats, when: canDocs } );
sdk.registerSlot( 'department.sections', { id: 'sign', module: M, order: 40, component: DepartmentSections, when: canDocs } );
sdk.registerSlot( 'department.card.counts', { id: 'sign', module: M, order: 40, component: DepartmentCardCounts, when: canDocs } );
sdk.registerSlot( 'home.stats', { id: 'sign', module: M, order: 40, component: HomeStats, when: canDocs } );
sdk.registerSlot( 'home.panels', { id: 'sign', module: M, order: 40, component: HomePanel, when: canDocs } );
sdk.registerSlot( 'settings.tabs', { id: 'sign', module: M, order: 40, label: 'Sign', component: DocsSettings, when: canDocs } );

// Lists the owner can edit under Settings → Lists & options.
sdk.extend( 'lookup.types', { id: 'doc_type', module: M, order: 70, label: 'Document types' } );

// What shows in the Activity list.
sdk.extend( 'activity.types', { id: 'sign-contracts', module: M, order: 70, type: { value: 'contracts', label: sdk.tp( 'contract' ) } } );
sdk.extend( 'activity.types', { id: 'sign-templates', module: M, order: 71, type: { value: 'contract-templates', label: 'Templates' } } );
