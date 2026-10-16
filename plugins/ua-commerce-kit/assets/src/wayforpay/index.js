import { __ } from '@wordpress/i18n';
import { decodeEntities } from '@wordpress/html-entities';
import { registerPaymentMethod } from '@woocommerce/blocks-registry';
import { getSetting } from '@woocommerce/settings';

const settings = getSetting( 'uack_wayforpay_data', {} );
const title = decodeEntities(
	settings.title || __( 'Card online', 'ua-commerce-kit' )
);

const Content = () => (
	<>
		{ settings.description && (
			<p>{ decodeEntities( settings.description ) }</p>
		) }
		{ settings.test && (
			<p className="uack-wfp-test">
				{ __(
					'Test mode: the payment page is real, but no money is taken. Do not enter a real card.',
					'ua-commerce-kit'
				) }
			</p>
		) }
	</>
);

const Label = ( { components } ) => (
	<components.PaymentMethodLabel text={ title } />
);

registerPaymentMethod( {
	name: 'uack_wayforpay',
	label: <Label />,
	content: <Content />,
	edit: <Content />,
	canMakePayment: () => true,
	ariaLabel: title,
	supports: { features: settings.supports ?? [ 'products' ] },
} );
