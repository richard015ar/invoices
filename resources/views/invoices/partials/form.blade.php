@php
    $defaultLineItems = $invoice->exists
        ? $invoice->lines->map(fn ($line) => [
            'catalog_item_id' => $line->catalog_item_id,
            'description' => $line->description,
            'quantity' => $line->quantity,
            'unit_price' => $line->unit_price,
            'tax_rate' => $line->tax_rate,
        ])->all()
        : ($clonedLines ?? [[
        'catalog_item_id' => null,
        'description' => '',
        'quantity' => 1,
        'unit_price' => 0,
        'tax_rate' => 0,
    ]]);

    $lineItems = old('lines', $defaultLineItems);
@endphp

<section class="panel" data-invoice-form data-catalog='@json($catalogItems)'>
    <div class="panel-head">
        <h2>{{ $title }}</h2>
    </div>

    <form action="{{ $action }}" method="POST" class="stack-lg">
        @csrf
        @if ($method !== 'POST')
            @method($method)
        @endif

        <div class="grid cols-3">
            <label>
                Numero
                <input name="invoice_number" value="{{ old('invoice_number', $invoice->invoice_number) }}" placeholder="Auto si vacio" />
            </label>
            <label>
                Fecha
                <input type="date" name="issue_date" required value="{{ old('issue_date', optional($invoice->issue_date)->format('Y-m-d') ?? $invoice->issue_date) }}" />
            </label>
            <label>
                Vencimiento
                <input type="date" name="due_date" value="{{ old('due_date', optional($invoice->due_date)->format('Y-m-d') ?? $invoice->due_date) }}" />
            </label>
        </div>

        <div class="grid cols-4">
            <label>
                Estado
                <select name="status">
                    @foreach (\App\Models\Invoice::STATUSES as $status)
                        <option value="{{ $status }}" @selected(old('status', $invoice->status) === $status)>{{ strtoupper($status) }}</option>
                    @endforeach
                </select>
            </label>
            <label>
                Moneda
                <input name="currency" maxlength="3" required value="{{ old('currency', $invoice->currency) }}" />
            </label>
            <label>
                Template
                <select name="template">
                    @foreach ($templates as $key => $template)
                        <option value="{{ $key }}" @selected(old('template', $invoice->template) === $key)>{{ $template['name'] }}</option>
                    @endforeach
                </select>
            </label>
            <label>
                Color
                <input type="color" name="accent_color" value="{{ old('accent_color', $invoice->accent_color) }}" />
            </label>
        </div>

        <div class="grid cols-2">
            <label>
                Desde (tu nombre)
                <input name="from_name" required value="{{ old('from_name', $invoice->from_name) }}" />
            </label>
            <label>
                Tu email
                <input type="email" name="from_email" value="{{ old('from_email', $invoice->from_email) }}" />
            </label>
        </div>

        <label>
            Tu direccion
            <textarea name="from_address" rows="2">{{ old('from_address', $invoice->from_address) }}</textarea>
        </label>

        <div class="grid cols-2">
            <label>
                Cliente
                <input name="client_name" required value="{{ old('client_name', $invoice->client_name) }}" />
            </label>
            <label>
                Email cliente
                <input type="email" name="client_email" value="{{ old('client_email', $invoice->client_email) }}" />
            </label>
        </div>

        <label>
            Direccion cliente
            <textarea name="client_address" rows="2">{{ old('client_address', $invoice->client_address) }}</textarea>
        </label>

        <label>
            Detalles cliente (opcional)
            <textarea name="client_details" rows="2" placeholder="Tax ID, VAT, CUIT, o datos adicionales">{{ old('client_details', $invoice->client_details) }}</textarea>
        </label>

        <div class="panel nested">
            <div class="panel-head">
                <h3>Items</h3>
                <button type="button" class="btn-secondary" data-add-line>+ Agregar item</button>
            </div>

            <div class="stack" data-lines>
                @foreach ($lineItems as $index => $line)
                    @php
                        $line = array_merge([
                            'catalog_item_id' => null,
                            'description' => '',
                            'quantity' => 1,
                            'unit_price' => 0,
                            'tax_rate' => 0,
                        ], is_array($line) ? $line : []);
                    @endphp
                    <div class="line-item" data-line>
                        <div class="grid cols-5">
                            <label>
                                Catalogo
                                <select name="lines[{{ $index }}][catalog_item_id]" data-catalog-select>
                                    <option value="">Manual</option>
                                    @foreach ($catalogItems as $item)
                                        <option value="{{ $item->id }}" @selected((string) ($line['catalog_item_id'] ?? '') === (string) $item->id)>{{ $item->name }}</option>
                                    @endforeach
                                </select>
                            </label>
                            <label class="span-2">
                                Descripcion
                                <input name="lines[{{ $index }}][description]" required value="{{ $line['description'] }}" data-description />
                            </label>
                            <label>
                                Cantidad
                                <input type="number" step="0.01" min="0" name="lines[{{ $index }}][quantity]" required value="{{ $line['quantity'] }}" data-quantity />
                            </label>
                            <label>
                                Precio
                                <input type="number" step="0.01" min="0" name="lines[{{ $index }}][unit_price]" required value="{{ $line['unit_price'] }}" data-unit-price />
                            </label>
                        </div>
                        <div class="grid cols-5">
                            <label>
                                Impuesto %
                                <input type="number" step="0.01" min="0" max="100" name="lines[{{ $index }}][tax_rate]" value="{{ $line['tax_rate'] }}" data-tax-rate />
                            </label>
                            <div class="line-total">Total: <span data-line-total>0.00</span></div>
                            <div class="allowance-hint" data-allowance-hint hidden></div>
                            <button type="button" class="btn-danger" data-remove-line>Eliminar</button>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        <label>
            Notas
            <textarea name="notes" rows="3">{{ old('notes', $invoice->notes) }}</textarea>
        </label>

        <div class="panel nested totals">
            <p>Subtotal: <strong data-subtotal>0.00</strong></p>
            <p>Impuestos: <strong data-tax-total>0.00</strong></p>
            <p>Total: <strong data-grand-total>0.00</strong></p>
        </div>

        <button class="btn-primary" type="submit">Guardar invoice</button>
    </form>
</section>

<template id="line-template">
    <div class="line-item" data-line>
        <div class="grid cols-5">
            <label>
                Catalogo
                <select data-name="catalog_item_id" data-catalog-select>
                    <option value="">Manual</option>
                    @foreach ($catalogItems as $item)
                        <option value="{{ $item->id }}">{{ $item->name }}</option>
                    @endforeach
                </select>
            </label>
            <label class="span-2">
                Descripcion
                <input data-name="description" required data-description />
            </label>
            <label>
                Cantidad
                <input type="number" step="0.01" min="0" data-name="quantity" required value="1" data-quantity />
            </label>
            <label>
                Precio
                <input type="number" step="0.01" min="0" data-name="unit_price" required value="0" data-unit-price />
            </label>
        </div>
        <div class="grid cols-5">
            <label>
                Impuesto %
                <input type="number" step="0.01" min="0" max="100" data-name="tax_rate" value="0" data-tax-rate />
            </label>
            <div class="line-total">Total: <span data-line-total>0.00</span></div>
            <div class="allowance-hint" data-allowance-hint hidden></div>
            <button type="button" class="btn-danger" data-remove-line>Eliminar</button>
        </div>
    </div>
</template>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        const formRoot = document.querySelector('[data-invoice-form]');
        if (!formRoot) return;

        const catalogItems = JSON.parse(formRoot.dataset.catalog || '[]');
        const linesContainer = formRoot.querySelector('[data-lines]');
        const addLineButton = formRoot.querySelector('[data-add-line]');
        const template = document.getElementById('line-template');

        const currency = () => (formRoot.querySelector('input[name="currency"]')?.value || 'USD').toUpperCase();
        const formatMoney = (amount, code = currency()) => `${code} ${Number(amount || 0).toFixed(2)}`;

        const updateAllowanceHint = (line, item = null) => {
            const hint = line.querySelector('[data-allowance-hint]');
            if (!hint) return;

            if (!item || !item.allowance_is_tracked) {
                hint.hidden = true;
                hint.textContent = '';
                return;
            }

            const remaining = Number(item.allowance_remaining_current_year || 0);
            const used = Number(item.allowance_used_current_year || 0);
            const annualLimit = Number(item.allowance_annual_limit || 0);
            const code = item.allowance_currency || 'CAD';
            const year = item.allowance_year || new Date().getFullYear();

            hint.textContent = `Disponible ${year}: ${formatMoney(remaining, code)} de ${formatMoney(annualLimit, code)} (usado: ${formatMoney(used, code)})`;
            hint.hidden = false;
        };

        const updateNames = () => {
            [...linesContainer.querySelectorAll('[data-line]')].forEach((line, index) => {
                line.querySelectorAll('[data-name]').forEach((input) => {
                    input.name = `lines[${index}][${input.dataset.name}]`;
                });
            });
        };

        const recalc = () => {
            let subtotal = 0;
            let taxTotal = 0;

            [...linesContainer.querySelectorAll('[data-line]')].forEach((line) => {
                const quantity = parseFloat(line.querySelector('[data-quantity]')?.value || 0);
                const price = parseFloat(line.querySelector('[data-unit-price]')?.value || 0);
                const taxRate = parseFloat(line.querySelector('[data-tax-rate]')?.value || 0);

                const lineSubtotal = quantity * price;
                const lineTax = lineSubtotal * (taxRate / 100);

                subtotal += lineSubtotal;
                taxTotal += lineTax;

                line.querySelector('[data-line-total]').textContent = `${currency()} ${lineSubtotal.toFixed(2)}`;
            });

            formRoot.querySelector('[data-subtotal]').textContent = `${currency()} ${subtotal.toFixed(2)}`;
            formRoot.querySelector('[data-tax-total]').textContent = `${currency()} ${taxTotal.toFixed(2)}`;
            formRoot.querySelector('[data-grand-total]').textContent = `${currency()} ${(subtotal + taxTotal).toFixed(2)}`;
        };

        const attachLineEvents = (line) => {
            line.querySelector('[data-remove-line]').addEventListener('click', () => {
                line.remove();
                updateNames();
                recalc();
            });

            line.querySelectorAll('input').forEach((input) => input.addEventListener('input', recalc));

            line.querySelector('[data-catalog-select]').addEventListener('change', (event) => {
                const selectedId = event.target.value;
                const item = catalogItems.find((entry) => String(entry.id) === String(selectedId));
                if (!item) {
                    updateAllowanceHint(line);
                    return;
                }

                line.querySelector('[data-description]').value = item.description || item.name;
                line.querySelector('[data-unit-price]').value = Number(item.default_unit_price || 0).toFixed(2);
                line.querySelector('[data-tax-rate]').value = Number(item.default_tax_rate || 0).toFixed(2);
                updateAllowanceHint(line, item);
                recalc();
            });

            const selectedId = line.querySelector('[data-catalog-select]')?.value;
            const selectedItem = catalogItems.find((entry) => String(entry.id) === String(selectedId));
            updateAllowanceHint(line, selectedItem || null);
        };

        addLineButton.addEventListener('click', () => {
            const node = template.content.firstElementChild.cloneNode(true);
            linesContainer.appendChild(node);
            updateNames();
            attachLineEvents(node);
            recalc();
        });

        [...linesContainer.querySelectorAll('[data-line]')].forEach((line) => {
            line.querySelectorAll('select, input, textarea').forEach((input) => {
                if (!input.dataset.name && input.name) {
                    const matches = input.name.match(/\[(.*?)\]$/);
                    if (matches) input.dataset.name = matches[1];
                }
            });
            attachLineEvents(line);
        });

        formRoot.querySelector('input[name="currency"]')?.addEventListener('input', recalc);
        recalc();
    });
</script>
