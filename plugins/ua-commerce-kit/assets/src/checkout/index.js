import { registerPlugin } from '@wordpress/plugins';
import { ExperimentalOrderShippingPackages } from '@woocommerce/blocks-checkout';
import Picker from './picker';
import './style.scss';

// The slot clones its children with the cart, the extensions data and the context as props.
const CheckoutPicker = ( { cart, extensions, context } ) =>
	context === 'woocommerce/checkout' ? (
		<Picker cart={ cart } extensions={ extensions } />
	) : null;

registerPlugin( 'uack-nova-poshta', {
	scope: 'woocommerce-checkout',
	render: () => (
		<ExperimentalOrderShippingPackages>
			<CheckoutPicker />
		</ExperimentalOrderShippingPackages>
	),
} );
