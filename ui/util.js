import { store, today } from './core';

/** Plain-word statuses for documents and their signers. */
export const CONTRACT_STATUS = {
	draft: { label: 'Draft', tone: 'neutral' },
	sent: { label: 'Awaiting signatures', tone: 'warning' },
	partially_signed: { label: 'Partly signed', tone: 'info' },
	signed: { label: 'Signed', tone: 'success' },
	declined: { label: 'Declined', tone: 'danger' },
	void: { label: 'Voided', tone: 'danger' },
	expired: { label: 'Expired', tone: 'neutral' },
};
export const CONTRACT_STATUS_OPTIONS = Object.entries( CONTRACT_STATUS ).map( ( [ value, d ] ) => ( { value, label: d.label } ) );
export const SIGNER_STATUS = {
	pending: { label: 'Waiting', tone: 'warning' },
	viewed: { label: 'Opened', tone: 'info' },
	signed: { label: 'Signed', tone: 'success' },
	declined: { label: 'Declined', tone: 'danger' },
};
export const statusOf = ( s ) => CONTRACT_STATUS[ s ] || { label: s || '—', tone: 'neutral' };
export const signerStatusOf = ( s ) => SIGNER_STATUS[ s ] || { label: s || '—', tone: 'neutral' };
export const WAITING = [ 'sent', 'partially_signed' ];

/** Who may use Documents (the server sends and saves only for these people). */
export const canDocs = () => !! ( store.caps.contracts || store.caps.admin );

export const fmtSize = ( bytes ) => {
	const n = Number( bytes ) || 0;
	if ( n < 1024 ) return n + ' B';
	if ( n < 1048576 ) return Math.round( n / 1024 ) + ' KB';
	return Math.round( ( n / 1048576 ) * 10 ) / 10 + ' MB';
};

// Date presets for the analytics bar.
const iso = ( d ) => d.toISOString().slice( 0, 10 );
const shift = ( dateStr, days ) => {
	const d = new Date( dateStr + 'T00:00:00Z' );
	d.setUTCDate( d.getUTCDate() + days );
	return iso( d );
};
/** { from, to } (inclusive, YYYY-MM-DD) for a preset id. */
export const rangeFor = ( id, custom = [] ) => {
	const to = today();
	if ( id === '30d' ) return { from: shift( to, -29 ), to };
	if ( id === '90d' ) return { from: shift( to, -89 ), to };
	if ( id === 'ytd' ) return { from: to.slice( 0, 4 ) + '-01-01', to };
	if ( id === '12m' ) {
		const d = new Date( to + 'T00:00:00Z' );
		d.setUTCMonth( d.getUTCMonth() - 11, 1 );
		return { from: iso( d ), to };
	}
	return { from: custom[ 0 ] || shift( to, -29 ), to: custom[ 1 ] || to };
};
