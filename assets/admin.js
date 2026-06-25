/* Checkout-Felder – Admin-JS (vanilla, kein Framework) */
(function () {
	'use strict';

	// Zähler für neue Feldindizes pro Abschnitt (verhindert doppelte name-Attribute).
	var sectionCounters = {};

	/**
	 * Berechnet den nächsten freien Index für einen Abschnitt.
	 * Zählt die vorhandenen .ckf-field-row-Elemente in der Tabelle.
	 *
	 * @param {string} section
	 * @returns {number}
	 */
	function nextIndex(section) {
		var table = document.querySelector('.ckf-fields-table[data-section="' + section + '"]');
		if (!table) { return 0; }
		return table.querySelectorAll('tbody .ckf-field-row').length;
	}

	/**
	 * Fügt eine neue Zeile für ein benutzerdefiniertes Feld in die Tabelle ein.
	 *
	 * @param {string} section  Abschnittsname (billing|shipping|order)
	 * @param {string} label    Bezeichnung des neuen Felds
	 * @param {string} type     Feldtyp (text|textarea|…)
	 */
	function addFieldRow(section, label, type) {
		var table = document.querySelector('.ckf-fields-table[data-section="' + section + '"]');
		if (!table) { return; }

		var tbody = table.querySelector('tbody');
		if (!tbody) { return; }

		// Schlüssel aus Label ableiten: Kleinbuchstaben, Leerzeichen → Unterstriche,
		// Sonderzeichen entfernen, führendes/nachfolgendes _ trimmen.
		var key = label
			.toLowerCase()
			.replace(/\s+/g, '_')
			.replace(/[^a-z0-9_]/g, '')
			.replace(/^_+|_+$/g, '');

		if (!key) {
			alert('Bitte eine gültige Bezeichnung eingeben.');
			return;
		}

		var idx  = nextIndex(section);
		var base = 'ckf_fields[' + section + '][' + idx + ']';

		// Erlaubte Typen aus den vorhandenen Select-Optionen lesen (erster Add-Row-Select).
		var typeSelectTemplate = table.querySelector('.ckf-new-type');
		var typeOptions = '';
		if (typeSelectTemplate) {
			for (var i = 0; i < typeSelectTemplate.options.length; i++) {
				var opt = typeSelectTemplate.options[i];
				var sel = opt.value === type ? ' selected' : '';
				typeOptions += '<option value="' + escAttr(opt.value) + '"' + sel + '>' + escHtml(opt.text) + '</option>';
			}
		} else {
			typeOptions = '<option value="' + escAttr(type) + '">' + escHtml(type) + '</option>';
		}

		var tr = document.createElement('tr');
		tr.className = 'ckf-field-row';
		tr.setAttribute('data-custom', '1');
		tr.innerHTML =
			'<td class="ckf-col-enabled">' +
				'<input type="checkbox" name="' + escAttr(base) + '[enabled]" value="1" checked>' +
			'</td>' +
			'<td class="ckf-col-key">' +
				'<input type="hidden" name="' + escAttr(base) + '[key]" value="' + escAttr(key) + '">' +
				'<input type="hidden" name="' + escAttr(base) + '[custom]" value="1">' +
				'<code>' + escHtml(key) + '</code>' +
			'</td>' +
			'<td class="ckf-col-label">' +
				'<input type="text" name="' + escAttr(base) + '[label]" value="' + escAttr(label) + '" class="regular-text">' +
			'</td>' +
			'<td class="ckf-col-placeholder">' +
				'<input type="text" name="' + escAttr(base) + '[placeholder]" value="" class="regular-text">' +
			'</td>' +
			'<td class="ckf-col-type">' +
				'<select name="' + escAttr(base) + '[type]">' + typeOptions + '</select>' +
			'</td>' +
			'<td class="ckf-col-required">' +
				'<input type="checkbox" name="' + escAttr(base) + '[required]" value="1">' +
			'</td>' +
			'<td class="ckf-col-position">' +
				'<input type="number" name="' + escAttr(base) + '[position]" value="' + ((idx + 1) * 10) + '" class="small-text" min="1">' +
			'</td>' +
			'<td class="ckf-col-actions">' +
				'<button type="button" class="button button-link-delete ckf-remove-field">Entfernen</button>' +
			'</td>';

		tbody.appendChild(tr);
	}

	/**
	 * Entfernt eine Feldzeile aus der Tabelle.
	 *
	 * @param {HTMLElement} btn Geklickter "Entfernen"-Button
	 */
	function removeFieldRow(btn) {
		var row = btn.closest('tr.ckf-field-row');
		if (row) {
			row.parentNode.removeChild(row);
		}
	}

	// -------------------------------------------------------------------------
	// Event-Delegation auf Dokumentebene
	// -------------------------------------------------------------------------
	document.addEventListener('click', function (e) {
		// "+ Feld hinzufügen"
		if (e.target && e.target.classList.contains('ckf-add-field')) {
			var section    = e.target.getAttribute('data-section');
			var addRow     = e.target.closest('.ckf-add-field-row');
			var labelInput = addRow ? addRow.querySelector('.ckf-new-label') : null;
			var typeSelect = addRow ? addRow.querySelector('.ckf-new-type') : null;
			var label      = labelInput ? labelInput.value.trim() : '';
			var type       = typeSelect ? typeSelect.value : 'text';

			if (!label) {
				alert('Bitte zuerst eine Bezeichnung eingeben.');
				return;
			}

			addFieldRow(section, label, type);

			// Eingabe zurücksetzen.
			if (labelInput) { labelInput.value = ''; }
			if (typeSelect) { typeSelect.selectedIndex = 0; }
		}

		// "Entfernen"-Button
		if (e.target && e.target.classList.contains('ckf-remove-field')) {
			if (window.confirm('Dieses Feld wirklich entfernen?')) {
				removeFieldRow(e.target);
			}
		}
	});

	// -------------------------------------------------------------------------
	// Hilfsfunktionen zum sicheren Einbetten in innerHTML
	// -------------------------------------------------------------------------
	function escHtml(str) {
		return String(str)
			.replace(/&/g, '&amp;')
			.replace(/</g, '&lt;')
			.replace(/>/g, '&gt;')
			.replace(/"/g, '&quot;');
	}

	function escAttr(str) {
		return String(str)
			.replace(/&/g, '&amp;')
			.replace(/"/g, '&quot;');
	}
})();
