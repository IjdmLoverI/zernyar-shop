/**
 * City and branch picker shown under the shipping options.
 */
import { __, sprintf } from '@wordpress/i18n';
import { useDispatch, useSelect } from '@wordpress/data';
import { useEffect, useState } from '@wordpress/element';
import { extensionCartUpdate } from '@woocommerce/blocks-checkout';
import {
	CHECKOUT_STORE_KEY,
	VALIDATION_STORE_KEY,
} from '@woocommerce/block-data';
import { getSetting } from '@woocommerce/settings';
import Combobox from './combobox';

const settings = getSetting( 'uack-nova-poshta_data', {} );
const ERROR_ID = 'uack-np-warehouse';

const TYPES = {
	branch: __( 'Branch', 'ua-commerce-kit' ),
	locker: __( 'Parcel locker', 'ua-commerce-kit' ),
	point: __( 'Pickup point', 'ua-commerce-kit' ),
};

const MESSAGES = {
	searching: __( 'Searching…', 'ua-commerce-kit' ),
	empty: __( 'Nothing found', 'ua-commerce-kit' ),
	failed: __(
		'Nova Poshta does not answer. Try again in a minute.',
		'ua-commerce-kit'
	),
	short: __( 'Type at least two letters', 'ua-commerce-kit' ),
};

async function get( path, params ) {
	const url = new URL( settings.restUrl + path );
	Object.entries( params ).forEach( ( [ key, value ] ) =>
		url.searchParams.set( key, value )
	);
	const response = await window.fetch( url, { credentials: 'same-origin' } );
	if ( ! response.ok ) {
		throw new Error( `HTTP ${ response.status }` );
	}
	return response.json();
}

function selectedMethod( cart ) {
	for ( const shippingPackage of cart?.shippingRates ?? [] ) {
		const rate = shippingPackage.shipping_rates.find( ( r ) => r.selected );
		if ( rate ) {
			return rate.method_id;
		}
	}
	return '';
}

export default function Picker( { cart, extensions } ) {
	const saved = extensions?.[ settings.namespace ] ?? {};
	const [ pick, setPick ] = useState( saved );
	const [ saving, setSaving ] = useState( false );
	const active = selectedMethod( cart ) === settings.methodId;

	const { setValidationErrors, clearValidationError } =
		useDispatch( VALIDATION_STORE_KEY );
	const { setExtensionData } = useDispatch( CHECKOUT_STORE_KEY );
	const error = useSelect(
		( select ) =>
			select( VALIDATION_STORE_KEY ).getValidationError( ERROR_ID ),
		[]
	);

	// The cart object is new on every update, so compare picks by their references.
	const key = ( value ) =>
		`${ value.city_ref ?? '' }|${ value.warehouse_ref ?? '' }`;
	const savedKey = key( saved );
	useEffect( () => {
		if ( ! pick.city_ref ) {
			setPick( saved );
		} else if ( savedKey !== key( pick ) && ! saving ) {
			// Another cart update saved the session without the pick; send it again.
			extensionCartUpdate( {
				namespace: settings.namespace,
				data: pick,
			} );
		}
	}, [ savedKey ] ); // eslint-disable-line react-hooks/exhaustive-deps

	// The checkout request carries the pick too, so a lost session cannot lose the order's branch.
	useEffect( () => {
		setExtensionData( settings.namespace, {
			city_ref: pick.city_ref ?? '',
			city: pick.city ?? '',
			warehouse_ref: pick.warehouse_ref ?? '',
		} );
	}, [ pick.city_ref, pick.warehouse_ref ] ); // eslint-disable-line react-hooks/exhaustive-deps

	useEffect( () => {
		if ( active && ! pick.warehouse_ref ) {
			setValidationErrors( {
				[ ERROR_ID ]: {
					message: __(
						'Choose a Nova Poshta branch or parcel locker.',
						'ua-commerce-kit'
					),
					hidden: true,
				},
			} );
		} else {
			clearValidationError( ERROR_ID );
		}
	}, [ active, pick.warehouse_ref ] ); // eslint-disable-line react-hooks/exhaustive-deps

	if ( ! active ) {
		return null;
	}

	const save = async ( next ) => {
		setPick( next );
		setSaving( true );
		try {
			await extensionCartUpdate( {
				namespace: settings.namespace,
				data: next,
			} );
		} finally {
			setSaving( false );
		}
	};

	const warehouseLabel = ( warehouse ) => warehouse.name;

	return (
		<fieldset className="uack-np-picker" aria-busy={ saving }>
			<legend className="uack-np-picker__legend">
				{ __( 'Where to deliver', 'ua-commerce-kit' ) }
			</legend>
			<Combobox
				id="uack-np-city"
				label={ __( 'City', 'ua-commerce-kit' ) }
				value={ pick.city ?? '' }
				minLength={ 2 }
				messages={ MESSAGES }
				search={ ( q ) => get( 'cities', { q } ) }
				optionLabel={ ( city ) => city.name }
				renderOption={ ( city ) => (
					<>
						<span className="uack-np-option__title">
							{ city.name }
						</span>
						<span className="uack-np-option__meta">
							{ city.label }
						</span>
					</>
				) }
				onPick={ ( city ) =>
					save( { city_ref: city.ref, city: city.name } )
				}
			/>
			<Combobox
				id="uack-np-warehouse"
				label={ __( 'Branch or parcel locker', 'ua-commerce-kit' ) }
				value={ pick.warehouse ?? '' }
				disabled={ ! pick.city_ref }
				messages={ { ...MESSAGES, short: '' } }
				search={ ( q ) =>
					get( 'warehouses', { city: pick.city_ref, q } )
				}
				optionLabel={ warehouseLabel }
				renderOption={ ( warehouse ) => (
					<>
						<span className="uack-np-option__title">
							{ sprintf(
								/* translators: 1: Branch, Parcel locker or Pickup point. 2: number. */
								__( '%1$s №%2$s', 'ua-commerce-kit' ),
								TYPES[ warehouse.type ] ?? TYPES.branch,
								warehouse.number
							) }
						</span>
						<span className="uack-np-option__meta">
							{ warehouse.name }
						</span>
					</>
				) }
				onPick={ ( warehouse ) =>
					save( {
						city_ref: pick.city_ref,
						city: pick.city,
						warehouse_ref: warehouse.ref,
						warehouse: warehouse.name,
						number: warehouse.number,
						type: warehouse.type,
						postcode: warehouse.postcode,
					} )
				}
				error={ error && ! error.hidden ? error.message : '' }
			/>
			{ ! pick.city_ref && (
				<p className="uack-np-picker__hint">
					{ __(
						'Start typing the city, then pick a branch, a parcel locker or a pickup point.',
						'ua-commerce-kit'
					) }
				</p>
			) }
		</fieldset>
	);
}
