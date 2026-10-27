import {
	DndContext,
	closestCenter,
	KeyboardSensor,
	PointerSensor,
	useSensor,
	useSensors,
} from '@dnd-kit/core';
import {
	arrayMove,
	SortableContext,
	sortableKeyboardCoordinates,
	useSortable,
	verticalListSortingStrategy,
} from '@dnd-kit/sortable';
import { CSS } from '@dnd-kit/utilities';
import ConnectionRow from '../ConnectionRow';

const SortableItem = ( props ) => {
	const { attributes, listeners, setNodeRef, transform, transition, isDragging } = useSortable( {
		id: props.id,
	} );

	const style = {
		transform: CSS.Transform.toString( transform ),
		transition,
		opacity: isDragging ? 0.85 : 1,
	};

	return (
		<div ref={ setNodeRef } style={ style } className={ isDragging ? 'ps-v2-conn-row-wrap is-dragging' : 'ps-v2-conn-row-wrap' }>
			<ConnectionRow
				{ ...props }
				dragHandleProps={ { ...attributes, ...listeners } }
			/>
		</div>
	);
};

const SortableConnectionList = ( {
	connections,
	onReorder,
	onToggleFallback,
	togglePending,
	onDelete,
} ) => {
	const sensors = useSensors(
		useSensor( PointerSensor ),
		useSensor( KeyboardSensor, { coordinateGetter: sortableKeyboardCoordinates } )
	);

	const ids = connections.map( ( c ) => String( c.id ) );

	const handleDragEnd = ( event ) => {
		const { active, over } = event;
		if ( ! over || active.id === over.id ) {
			return;
		}
		const oldIndex = ids.indexOf( String( active.id ) );
		const newIndex = ids.indexOf( String( over.id ) );
		const newOrder = arrayMove( connections, oldIndex, newIndex ).map( ( c ) => c.id );
		onReorder( newOrder );
	};

	return (
		<DndContext sensors={ sensors } collisionDetection={ closestCenter } onDragEnd={ handleDragEnd }>
			<SortableContext items={ ids } strategy={ verticalListSortingStrategy }>
				<ul className="ps-v2-conn-list">
					{ connections.map( ( connection, index ) => (
						<SortableItem
							key={ connection.id }
							id={ String( connection.id ) }
							connection={ connection }
							index={ connection.id }
							isPrimary={ index === 0 }
							onToggleFallback={ onToggleFallback }
							togglePending={ togglePending }
							onDelete={ onDelete }
						/>
					) ) }
				</ul>
			</SortableContext>
		</DndContext>
	);
};

export default SortableConnectionList;
