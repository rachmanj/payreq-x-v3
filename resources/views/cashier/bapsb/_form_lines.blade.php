@php
    $lineIndex = 0;
@endphp
@foreach ($grouped as $account => $items)
    <div class="card card-outline mb-3">
        <div class="card-header">
            <strong>{{ $account }}</strong>
        </div>
        <div class="card-body p-0 table-responsive">
            <table class="table table-sm table-bordered mb-0">
                <thead class="thead-light">
                    <tr>
                        <th>Type</th>
                        <th>Number</th>
                        <th>Date</th>
                        <th>Amount</th>
                        <th>Status</th>
                        <th>Physical</th>
                        <th>Location</th>
                        <th>Note</th>
                        <th>Remarks</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($items as $item)
                        @php
                            $bilyet = $item instanceof \App\Models\BapsbLine ? $item->bilyet : $item;
                            $line = $item instanceof \App\Models\BapsbLine ? $item : null;
                            $bilyetId = $bilyet->id;
                            $physical = $line ? $line->physical_present : true;
                            $location = $line?->location ?? 'Brankas Site';
                            $locationNote = $line?->location_note;
                            $remarks = $line?->remarks;
                        @endphp
                        <tr>
                            <td>{{ \App\Models\Bilyet::TYPE_LABELS[$bilyet->type] ?? $bilyet->type }}</td>
                            <td>{{ $bilyet->prefix }}{{ $bilyet->nomor }}</td>
                            <td>{{ $bilyet->bilyet_date?->format('d M Y') }}</td>
                            <td class="text-right">{{ number_format((float) $bilyet->amount, 0, ',', '.') }}</td>
                            <td>{{ \App\Models\Bilyet::STATUS_LABELS[$bilyet->status] ?? $bilyet->status }}</td>
                            <td>
                                <input type="hidden" name="lines[{{ $lineIndex }}][bilyet_id]" value="{{ $bilyetId }}">
                                @if ($line)
                                    <input type="hidden" name="lines[{{ $lineIndex }}][id]" value="{{ $line->id }}">
                                @endif
                                <label class="mr-2"><input type="radio" name="lines[{{ $lineIndex }}][physical_present]" value="1" {{ $physical ? 'checked' : '' }} required> Present</label>
                                <label><input type="radio" name="lines[{{ $lineIndex }}][physical_present]" value="0" {{ ! $physical ? 'checked' : '' }}> Missing</label>
                            </td>
                            <td>
                                <select name="lines[{{ $lineIndex }}][location]" class="form-control form-control-sm" required>
                                    @foreach ($locations as $loc)
                                        <option value="{{ $loc }}" @selected($location === $loc)>{{ $loc }}</option>
                                    @endforeach
                                </select>
                            </td>
                            <td>
                                <input type="text" name="lines[{{ $lineIndex }}][location_note]" class="form-control form-control-sm" value="{{ $locationNote }}" maxlength="500">
                            </td>
                            <td>
                                <input type="text" name="lines[{{ $lineIndex }}][remarks]" class="form-control form-control-sm" value="{{ $remarks }}" maxlength="2000">
                            </td>
                        </tr>
                        @php $lineIndex++; @endphp
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
@endforeach
