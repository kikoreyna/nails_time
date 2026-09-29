const bookingForm = document.querySelector('.booking-form');

if (bookingForm) {
	const dateInput = bookingForm.querySelector('input[name="appointment_date"]');
	const serviceInputs = [...bookingForm.querySelectorAll('input[name="service_ids[]"]')];
	const slotContainer = document.querySelector('#time-slots');
	const startTimeInput = document.querySelector('#start-time');
	const selectedCount = document.querySelector('#selected-count');
	const selectionTotal = document.querySelector('#selection-total');
	let availabilityRequest;

	const selectedServices = () => serviceInputs.filter((input) => input.checked);

	const updateSummary = () => {
		const selected = selectedServices();
		const minutes = selected.reduce((total, input) => total + Number(input.dataset.duration), 0);
		const price = selected.reduce((total, input) => total + Number(input.dataset.price), 0);

		selectedCount.textContent = `${selected.length} ${selected.length === 1 ? 'servicio seleccionado' : 'servicios seleccionados'}`;
		selectionTotal.textContent = `$${new Intl.NumberFormat('es-MX', { maximumFractionDigits: 0 }).format(price)} · ${minutes} min`;
	};

	const renderSlots = (slots, previousSelection) => {
		slotContainer.replaceChildren();

		if (slots.length === 0) {
			const emptyMessage = document.createElement('p');
			emptyMessage.className = 'slot-prompt';
			emptyMessage.textContent = 'No hay horarios libres. Prueba con otra fecha.';
			slotContainer.append(emptyMessage);
			startTimeInput.value = '';
			return;
		}

		slots.forEach((slot) => {
			const button = document.createElement('button');
			const time = document.createElement('strong');
			const end = document.createElement('small');

			button.type = 'button';
			button.className = 'time-option';
			button.setAttribute('aria-pressed', 'false');
			time.textContent = slot.time;
			end.textContent = `hasta ${slot.end}`;
			button.append(time, end);
			button.addEventListener('click', () => {
				slotContainer.querySelectorAll('.time-option').forEach((option) => {
					option.classList.remove('is-selected');
					option.setAttribute('aria-pressed', 'false');
				});
				button.classList.add('is-selected');
				button.setAttribute('aria-pressed', 'true');
				startTimeInput.value = slot.time;
				slotContainer.removeAttribute('data-error');
			});
			slotContainer.append(button);

			if (slot.time === previousSelection) {
				button.click();
			}
		});
	};

	const loadAvailability = async () => {
		const selected = selectedServices();
		const date = dateInput.value;

		slotContainer.removeAttribute('data-error');
		updateSummary();

		if (!date || selected.length === 0) {
			slotContainer.innerHTML = '<p class="slot-prompt">Primero elige servicios y fecha.</p>';
			startTimeInput.value = '';
			return;
		}

		availabilityRequest?.abort();
		availabilityRequest = new AbortController();
		const previousSelection = startTimeInput.value;
		slotContainer.innerHTML = '<p class="slot-prompt">Buscando horarios libres...</p>';

		const parameters = new URLSearchParams({ date });
		selected.forEach((input) => parameters.append('services[]', input.value));

		try {
			const response = await fetch(`${bookingForm.dataset.availabilityUrl}?${parameters}`, {
				headers: { Accept: 'application/json' },
				signal: availabilityRequest.signal,
			});

			if (!response.ok) {
				throw new Error('No fue posible consultar la disponibilidad.');
			}

			const data = await response.json();
			renderSlots(data.slots, previousSelection);
		} catch (error) {
			if (error.name !== 'AbortError') {
				slotContainer.innerHTML = '<p class="slot-prompt">No pudimos cargar los horarios. Intenta de nuevo.</p>';
			}
		}
	};

	serviceInputs.forEach((input) => input.addEventListener('change', loadAvailability));
	dateInput.addEventListener('change', loadAvailability);
	bookingForm.addEventListener('submit', (event) => {
		if (selectedServices().length === 0 || !startTimeInput.value) {
			event.preventDefault();
			slotContainer.setAttribute('data-error', 'Selecciona tus servicios, fecha y horario para continuar.');
			slotContainer.scrollIntoView({ behavior: 'smooth', block: 'center' });
		}
	});

	updateSummary();
	if (dateInput.value && selectedServices().length > 0) {
		loadAvailability();
	}

	window.setInterval(() => {
		if (document.visibilityState === 'visible') {
			loadAvailability();
		}
	}, 30000);
}
