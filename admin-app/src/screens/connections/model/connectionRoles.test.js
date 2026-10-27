import { deriveRole } from './connectionRoles';

describe( 'deriveRole', () => {
	const connections = [
		{ title: 'Primary' },
		{ fallback_enabled: true },
		{ fallback_enabled: false },
		{ fallback_enabled: true },
	];

	it( 'marks index 0 as Primary', () => {
		expect( deriveRole( 0, connections[ 0 ], connections ).role ).toBe( 'primary' );
	} );

	it( 'computes fallback priority among enabled rows', () => {
		expect( deriveRole( 1, connections[ 1 ], connections ).roleLabel ).toBe( 'Fallback 1' );
		expect( deriveRole( 3, connections[ 3 ], connections ).roleLabel ).toBe( 'Fallback 2' );
	} );

	it( 'marks disabled secondary as Inactive', () => {
		expect( deriveRole( 2, connections[ 2 ], connections ).role ).toBe( 'inactive' );
	} );
} );
