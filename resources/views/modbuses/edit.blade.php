@extends('layouts.admin')
@section('title', __('Edit Modbus'))
@section('content')
    <div class="container">
        <div class="row">
            <div class="col-lg-12">
                <div class="card">
                    <div class="card-body">
                        <h4 class="card-title">{{ __('Edit Modbus') }}</h4>
                        <form action="{{ route('modbuses.update', $modbus->id) }}" method="POST">
                            @csrf
                            @method('PUT')
                            
                            <div class="form-group">
                                <label for="name">{{ __('Name') }}</label>
                                <input type="text" class="form-control" id="name" name="name" value="{{ $modbus->name }}">
                            </div>
                            
                            <div class="form-group">
                                <label for="json_api">{{ __('JSON API VALUE') }}</label>
                                <textarea class="form-control" id="json_api" name="json_api" rows="3">{{ $modbus->json_api }}</textarea>
                            </div>
                            
                            <div class="form-group">
                                <label for="mqtt_topic_modbus">{{ __('MQTT Topic Modbus') }}</label>
                                <input type="text" class="form-control" id="mqtt_topic_modbus" name="mqtt_topic_modbus" value="{{ $modbus->mqtt_topic_modbus }}">
                            </div>
                            
                            <div class="form-group">
                                <label for="token">{{ __('Token') }}</label>
                                <input type="text" class="form-control" id="token" name="token" value="{{ $modbus->token }}">
                            </div>

                            <div class="form-group">
                                <label for="dimension_id">{{ __('Dimension ID') }}</label>
                                <input type="text" class="form-control" id="dimension_id" name="dimension_id" value="{{ $modbus->dimension_id }}">
                            </div>
                            
                            <div class="form-group">
                                <label for="dimension">{{ __('Dimension') }}</label>
                                <input type="text" class="form-control" id="dimension" name="dimension" value="{{ $modbus->dimension }}">
                            </div>
                            
                            <div class="form-group">
                                <label for="max_kg">{{ __('Max KG') }}</label>
                                <input type="text" class="form-control" id="max_kg" name="max_kg" value="{{ $modbus->max_kg }}">
                            </div>

                            <div class="form-group">
                                <label for="rep_number">{{ __('Repetition Number') }}</label>
                                <input type="text" class="form-control" id="rep_number" name="rep_number" value="{{ $modbus->rep_number }}">
                            </div>

                            <div class="form-group">
                                <label for="tara">{{ __('Tara') }}</label>
                                <input type="text" class="form-control" id="tara" name="tara" value="{{ $modbus->tara }}">
                            </div>

                            <div class="form-group">
                                <label for="tara_calibrate">{{ __('Tara Calibrate') }}</label>
                                <input type="text" class="form-control" id="tara_calibrate" name="tara_calibrate" value="{{ $modbus->tara_calibrate }}">
                            </div>

                            <div class="form-group">
                                <label for="calibration_type">{{ __('Calibration Type') }}</label>
                                <select class="form-control" id="calibration_type" name="calibration_type" required>
                                    <option value="none" {{ $modbus->calibration_type == 'none' ? 'selected' : '' }}>Sin Tara</option>
                                    <option value="software" {{ $modbus->calibration_type == 'software' ? 'selected' : '' }}>Tara por Software</option>
                                    <option value="hardware" {{ $modbus->calibration_type == 'hardware' ? 'selected' : '' }}>Tara por Hardware</option>
                                </select>
                            </div>

                            <div class="form-group">
                                <label for="min_kg">{{ __('Min KG') }}</label>
                                <input type="text" class="form-control" id="min_kg" name="min_kg" value="{{ $modbus->min_kg }}">
                            </div>

                            <div class="form-group">
                                <label for="last_kg">{{ __('Last KG') }}</label>
                                <input type="text" class="form-control" id="last_kg" name="last_kg" value="{{ $modbus->last_kg }}">
                            </div>

                            <div class="form-group">
                                <label for="last_rep">{{ __('Last Repetition') }}</label>
                                <input type="text" class="form-control" id="last_rep" name="last_rep" value="{{ $modbus->last_rep }}">
                            </div>

                            <div class="form-group">
                                <label for="rec_box">{{ __('Rec Box') }}</label>
                                <input type="text" class="form-control" id="rec_box" name="rec_box" value="{{ $modbus->rec_box }}">
                            </div>

                            <div class="form-group">
                                <label for="last_value">{{ __('Last Value') }}</label>
                                <input type="text" class="form-control" id="last_value" name="last_value" value="{{ $modbus->last_value }}">
                            </div>

                            <div class="form-group">
                                <label for="variacion_number">{{ __('Variation Number') }}</label>
                                <input type="text" class="form-control" id="variacion_number" name="variacion_number" value="{{ $modbus->variacion_number }}">
                            </div>

                            <div class="form-group">
                                <label for="model_name">{{ __('Model Name') }}</label>
                                <select class="form-control" id="model_name" name="model_name" required>
                                    <option value="weight" {{ $modbus->model_name == 'weight' ? 'selected' : '' }}>Weight</option>
                                    <option value="height" {{ $modbus->model_name == 'height' ? 'selected' : '' }}>Height</option>
                                    <option value="lifeTraficMonitor" {{ $modbus->model_name == 'lifeTraficMonitor' ? 'selected' : '' }}>Life Traffic Monitor</option>
                                </select>
                            </div>

                            <div class="form-group">
                                <label for="dimension_default">{{ __('Dimension Default') }}</label>
                                <input type="text" class="form-control" id="dimension_default" name="dimension_default" value="{{ $modbus->dimension_default }}">
                            </div>

                            <div class="form-group">
                                <label for="dimension_max">{{ __('Dimension Max') }}</label>
                                <input type="text" class="form-control" id="dimension_max" name="dimension_max" value="{{ $modbus->dimension_max }}">
                            </div>

                            <div class="form-group">
                                <label for="dimension_variacion">{{ __('Dimension Variation') }}</label>
                                <input type="text" class="form-control" id="dimension_variacion" name="dimension_variacion" value="{{ $modbus->dimension_variacion }}">
                            </div>

                            <div class="form-group">
                                <label for="offset_meter">{{ __('Offset Meter') }}</label>
                                <input type="text" class="form-control" id="offset_meter" name="offset_meter" value="{{ $modbus->offset_meter }}">
                            </div>

                            <div class="form-group">
                                <label for="printer_id">{{ __('Printer ID') }}</label>
                                <input type="text" class="form-control" id="printer_id" name="printer_id" value="{{ $modbus->printer_id }}">
                            </div>

                            <div class="form-group">
                                <label for="unic_code_order">{{ __('Unic Code Order') }}</label>
                                <input type="text" class="form-control" id="unic_code_order" name="unic_code_order" value="{{ $modbus->unic_code_order }}">
                            </div>

                            <div class="form-group">
                                <label for="shift_type">{{ __('Shift Type') }}</label>
                                <input type="text" class="form-control" id="shift_type" name="shift_type" value="{{ $modbus->shift_type }}">
                            </div>

                            <div class="form-group">
                                <label for="event">{{ __('Event') }}</label>
                                <input type="text" class="form-control" id="event" name="event" value="{{ $modbus->event }}">
                            </div>

                            <div class="form-group">
                                <label for="downtime_count">{{ __('Downtime Count') }}</label>
                                <input type="number" class="form-control" id="downtime_count" name="downtime_count" value="{{ $modbus->downtime_count }}">
                            </div>

                            <div class="form-group">
                                <label for="optimal_production_time">{{ __('Optimal Production Time') }}</label>
                                <input type="number" class="form-control" id="optimal_production_time" name="optimal_production_time" value="{{ $modbus->optimal_production_time }}">
                            </div>

                            <div class="form-group">
                                <label for="reduced_speed_time_multiplier">{{ __('Reduced Speed Time Multiplier') }}</label>
                                <input type="number" class="form-control" id="reduced_speed_time_multiplier" name="reduced_speed_time_multiplier" value="{{ $modbus->reduced_speed_time_multiplier }}">
                            </div>

                            <button type="submit" class="btn btn-primary">{{ __('Save') }}</button>
                            <a href="{{ route('modbuses.index', $modbus->production_line_id) }}" class="btn btn-secondary">{{ __('Cancel') }}</a>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
