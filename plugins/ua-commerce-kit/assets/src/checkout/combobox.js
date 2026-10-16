/**
 * Text field with a list of suggestions, following the ARIA combobox pattern.
 */
import { useEffect, useRef, useState } from '@wordpress/element';

const DELAY = 250;

/**
 * @typedef {{ ref: string }} Option
 *
 * @param {Object}                                        props
 * @param {string}                                        props.id           Field ID.
 * @param {string}                                        props.label        Visible label.
 * @param {string}                                        props.value        Text of the picked option.
 * @param {(query: string) => Promise<Option[]>}          props.search       Throws when the service fails.
 * @param {(option: Option) => void}                      props.onPick       Called with the picked option.
 * @param {(option: Option) => string}                    props.optionLabel  Text shown in the field.
 * @param {(option: Option) => import('react').ReactNode} props.renderOption Content of the list item.
 * @param {number}                                        props.minLength    Characters needed before searching.
 * @param {Object<string, string>}                        props.messages     searching, empty, failed and short.
 * @param {boolean}                                       props.disabled
 * @param {string}                                        props.error        Validation message to show under the field.
 */
export default function Combobox( {
	id,
	label,
	value,
	search,
	onPick,
	optionLabel,
	renderOption,
	minLength = 0,
	messages,
	disabled = false,
	error = '',
} ) {
	const [ text, setText ] = useState( value );
	const [ options, setOptions ] = useState( [] );
	const [ open, setOpen ] = useState( false );
	const [ active, setActive ] = useState( -1 );
	const [ status, setStatus ] = useState( 'idle' );
	const [ focused, setFocused ] = useState( false );
	const request = useRef( 0 );
	const listId = `${ id }-list`;

	useEffect( () => setText( value ), [ value ] );

	const run = ( query ) => {
		const current = ++request.current;
		if ( query.trim().length < minLength ) {
			setOptions( [] );
			setStatus( 'short' );
			return;
		}
		setStatus( 'searching' );
		window.setTimeout( async () => {
			if ( current !== request.current ) {
				return;
			}
			try {
				const found = await search( query.trim() );
				if ( current === request.current ) {
					setOptions( found );
					setActive( found.length ? 0 : -1 );
					setStatus( found.length ? 'ready' : 'empty' );
				}
			} catch {
				if ( current === request.current ) {
					setOptions( [] );
					setStatus( 'failed' );
				}
			}
		}, DELAY );
	};

	const pick = ( option ) => {
		setText( optionLabel( option ) );
		setOpen( false );
		setOptions( [] );
		onPick( option );
	};

	const onKeyDown = ( event ) => {
		if ( event.key === 'ArrowDown' || event.key === 'ArrowUp' ) {
			event.preventDefault();
			if ( ! open ) {
				setOpen( true );
				run( text );
				return;
			}
			const step = event.key === 'ArrowDown' ? 1 : -1;
			setActive( ( index ) =>
				options.length
					? ( index + step + options.length ) % options.length
					: -1
			);
		} else if ( event.key === 'Enter' && open && options[ active ] ) {
			event.preventDefault();
			pick( options[ active ] );
		} else if ( event.key === 'Escape' && open ) {
			event.preventDefault();
			setOpen( false );
			setText( value );
		}
	};

	const message =
		status === 'ready' || status === 'idle' ? '' : messages[ status ];
	const activeId =
		open && options[ active ] ? `${ id }-${ active }` : undefined;

	return (
		<div
			className={ [
				'wc-block-components-text-input',
				'uack-np-combobox',
				text || focused ? 'is-active' : '',
				error ? 'has-error' : '',
			].join( ' ' ) }
		>
			<input
				id={ id }
				type="text"
				role="combobox"
				autoComplete="off"
				aria-autocomplete="list"
				aria-expanded={ open }
				aria-controls={ listId }
				aria-activedescendant={ activeId }
				aria-invalid={ error ? 'true' : undefined }
				aria-describedby={ error ? `${ id }-error` : undefined }
				value={ text }
				disabled={ disabled }
				onChange={ ( event ) => {
					setText( event.target.value );
					setOpen( true );
					run( event.target.value );
				} }
				onFocus={ () => {
					setFocused( true );
					setOpen( true );
					run( text === value ? '' : text );
				} }
				onBlur={ () => {
					setFocused( false );
					setOpen( false );
					setText( value );
				} }
				onKeyDown={ onKeyDown }
			/>
			<label htmlFor={ id }>{ label }</label>
			<div className="screen-reader-text" aria-live="polite">
				{ open ? message : '' }
			</div>
			{ open && (
				<ul
					id={ listId }
					role="listbox"
					className="uack-np-combobox__list"
				>
					{ message && (
						<li
							className="uack-np-combobox__message"
							role="presentation"
						>
							{ message }
						</li>
					) }
					{ options.map( ( option, index ) => (
						<li
							key={ option.ref }
							id={ `${ id }-${ index }` }
							role="option"
							aria-selected={ index === active }
							className={
								'uack-np-combobox__option' +
								( index === active ? ' is-active' : '' )
							}
							onMouseDown={ ( event ) => {
								event.preventDefault();
								pick( option );
							} }
							onMouseEnter={ () => setActive( index ) }
						>
							{ renderOption( option ) }
						</li>
					) ) }
				</ul>
			) }
			{ error && (
				<div
					className="wc-block-components-validation-error"
					role="alert"
				>
					<p id={ `${ id }-error` }>{ error }</p>
				</div>
			) }
		</div>
	);
}
