/** Table and form configuration for documents (contracts, agreements, NDAs…), and their templates. */
import { h } from 'vue';
import { sdk, navigate, fmtDate, dept, options, labelOf, colorOf } from './core';
import { CONTRACT_STATUS_OPTIONS, statusOf } from './util';

const muted = ( t ) => h( 'span', { class: 'bd-muted' }, t );
const pill = ( def, text ) => h( 'span', { class: [ 'bd-badge', 'bd-badge--' + ( def ? def.tone : 'neutral' ) ] }, text || ( def ? def.label : '' ) );
const tint = ( type, key ) => {
	const c = colorOf( type, key );
	return h( 'span', { class: 'bd-badge bd-badge--neutral', style: c ? { color: c, background: c + '1a' } : undefined }, labelOf( type, key ) );
};
const deptField = ( help ) => ( { name: 'department_id', label: dept().one, type: 'remote', remote: { resource: 'departments', label: ( b ) => b.name, params: () => ( { status: 'active' } ) }, help: help || '' } );
const deptFilter = () => ( { name: 'department_id', label: dept().one, remote: { resource: 'departments', label: ( b ) => b.name, params: { status: 'active' } } } );
const typeFilter = () => ( { name: 'doc_type', label: 'Type', options: options( 'doc_type' ) } );

export const CONTRACT_FIELDS = [ 'client_name', 'client_contact', 'client_email', 'client_address', 'company', 'company_address', 'date', 'contract_title', 'contract_number' ];

export const contractsConfig = ( { fixed = {} } = {} ) => ( {
	title: sdk.tp( 'contract' ),
	description: 'Agreements your ' + sdk.tp( 'client' ).toLowerCase() + ' sign online — with the proof to back them up.',
	resource: 'contracts',
	bulkDepartment: true,
	singular: sdk.t( 'contract' ).toLowerCase(),
	newLabel: `New ${ sdk.t( 'contract' ).toLowerCase() }`,
	emptyText: `No ${ sdk.tp( 'contract' ).toLowerCase() } yet. Create one from a template and send it for signature.`,
	filters: [ { name: 'status', label: 'Status', options: CONTRACT_STATUS_OPTIONS }, typeFilter(), ...( fixed.client_id ? [] : [ deptFilter() ] ) ],
	defaults: { ...fixed },
	onNew: () => navigate( '/contracts/new' + ( fixed.client_id ? `?client=${ fixed.client_id }` : '' ) ),
	onEdit: ( r ) => navigate( `/contracts/${ r.id }` ),
	columns: [
		{ label: sdk.t( 'contract' ), minWidth: 280, render: ( r ) => h( 'div', [ h( 'a', { class: 'bd-strong bd-link', href: `#/contracts/${ r.id }` }, r.title ), h( 'div', { class: 'bd-muted bd-small' }, [ r.number, fixed.client_id ? '' : r.client_name, r.department_name ].filter( Boolean ).join( ' · ' ) ) ] ) },
		{ label: 'Type', minWidth: 110, render: ( r ) => tint( 'doc_type', r.doc_type || 'contract' ) },
		{ label: 'Status', minWidth: 160, render: ( r ) => pill( statusOf( r.status ) ) },
		{ label: 'Signatures', minWidth: 130, render: ( r ) => ( r.signer_total ? h( 'span', { class: r.signer_done === r.signer_total ? '' : 'bd-muted' }, `${ r.signer_done } of ${ r.signer_total } signed` ) : muted( '—' ) ) },
		{ label: 'Sent', minWidth: 120, render: ( r ) => ( r.sent_at ? fmtDate( r.sent_at ) : muted( 'Not sent' ) ) },
		{ label: 'Completed', minWidth: 120, render: ( r ) => ( r.completed_at ? fmtDate( r.completed_at ) : muted( '—' ) ) },
	],
	fields: [],
} );

export const contractTemplatesConfig = () => ( {
	title: 'Templates',
	description: 'Reusable agreements. Insert fields like the ' + sdk.t( 'client' ).toLowerCase() + ' name and they fill in automatically.',
	resource: 'contract-templates',
	singular: 'template',
	newLabel: 'New template',
	emptyText: 'No templates yet. Add your standard service agreement, NDA or proposal terms.',
	filters: [ typeFilter() ],
	defaults: { is_active: true, body: '', doc_type: 'contract' },
	columns: [
		{ label: 'Template', minWidth: 300, render: ( r ) => h( 'div', [ h( 'div', { class: 'bd-strong' }, r.name ), r.description ? h( 'div', { class: 'bd-muted bd-small' }, r.description ) : null ] ) },
		{ label: 'Type', minWidth: 110, render: ( r ) => tint( 'doc_type', r.doc_type || 'contract' ) },
		{ label: 'Status', minWidth: 100, render: ( r ) => pill( r.is_active ? { label: 'Active', tone: 'success' } : { label: 'Hidden', tone: 'neutral' } ) },
	],
	fields: [
		{ name: 'name', label: 'Template name', required: true, wide: true, placeholder: 'e.g. Website design agreement' },
		{ name: 'description', label: 'When do you use it?', wide: true },
		{ name: 'doc_type', label: 'Type', type: 'select', options: () => options( 'doc_type' ) },
		deptField( `${ sdk.tp( 'contract' ) } made from this template start in this ${ dept().lower }.` ),
		{ name: 'body', label: 'Agreement text', type: 'richtext', wide: true, fields: CONTRACT_FIELDS, minHeight: 360, importable: true, help: `Use “Insert a field” so names and dates fill in for each ${ sdk.t( 'client' ).toLowerCase() }.` },
		{ name: 'is_active', label: 'Available', type: 'checkbox', checkLabel: 'Show in the template list' },
	],
} );
