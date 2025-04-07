@extends('layouts.admin')
@section('title', __('Create Modbus'))
@section('content')
    <div class="container">
        <div class="row">
            <div class="col-lg-12">
                <div class="card">
                    <div class="card-body">
                        <h4 class="card-title">{{ __('Create Modbus') }}</h4>
                        <form action="{{ route('modbuses.store', $production_line_id) }}" method="POST">
                            @csrf
                            <div class="form-group">
                                <label for="name">{{ __('Name') }}</label>
                                <input type="text" class="form-control" id="name" name="name">
                            </div>

                            <div class="form-group">
                                <label for="json_api">{{ __('JSON API VALUE') }}</label>
                                <textarea class="form-control" id="json_api" name="json_api" rows="3"></textarea>
                            </div>

                            <div class="form-group">
                                <label for="mqtt_topic_modbus">{{ __('MQTT Topic Modbus') }}</label>
                                <input type="text" class="form-control" id="mqtt_topic_modbus" name="mqtt_topic_modbus">
                            </div>

                            <div class="form-group">
                                <label for="mqtt_topic">{{ __('MQTT Topic') }}</label>
                                <input type="text" class="form-control" id="mqtt_topic" name="mqtt_topic">
                            </div>

                            <div class="form-group">
                                <label for="token">{{ __('Token') }}</label>
                                <input type="text" class="form-control" id="token" name="token" value="{{ uniqid() }}-{{ uniqid() }}-{{ uniqid() }}-{{ uniqid() }}">
                            </div>

                            <div class="form-group">
                                <label for="dimension_id">{{ __('Dimension ID') }}</label>
                                <input type="text" class="form-control" id="dimension_id" name="dimension_id">
                            </div>

                            <div class="form-group">
                                <label for="dimension">{{ __('Dimension') }}</label>
                                <input type="text" class="form-control" id="dimension" name="dimension">
                            </div>

                            <div class="form-group">
                                <label for="conversion_factor">{{ __('Conversion Factor') }}</label>
                                <input type="text" class="form-control" id="conversion_factor" name="conversion_factor" value="1">
                            </div>

                            <div class="form-group">
                                <label for="min_correction_percentage">{{ __('Min Correction Percentage') }}</label>
                                <input type="text" class="form-control" id="min_correction_percentage" name="min_correction_percentage" value="20">
                            </div>

                            <div class="form-group">
                                <label for="max_correction_percentage">{{ __('Max Correction Percentage') }}</label>
                                <input type="text" class="form-control" id="max_correction_percentage" name="max_correction_percentage" value="98">
                            </div>

                            <div class="form-group">
                                <label for="optimal_production_time">{{ __('Optimal Production Time') }}</label>
                                <input type="number" class="form-control" id="optimal_production_time" name="optimal_production_time" value="100">
                            </div>

                            <div class="form-group">
                                <label for="reduced_speed_time_multiplier">{{ __('Reduced Speed Time Multiplier') }}</label>
                                <input type="number" class="form-control" id="reduced_speed_time_multiplier" name="reduced_speed_time_multiplier" value="3">
                            </div>

                            <div class="form-group">
                                <label for="total_kg_order">{{ __('Total KG Order') }}</label>
                                <input type="text" class="form-control" id="total_kg_order" name="total_kg_order" value="0">
                            </div>

                            <div class="form-group">
                                <label for="total_kg_shift">{{ __('Total KG Shift') }}</label>
                                <input type="text" class="form-control" id="total_kg_shift" name="total_kg_shift" value="0">
                            </div>

                            <div class="form-group">
                                <label for="max_kg">{{ __('Max KG') }}</label>
                                <input type="text" class="form-control" id="max_kg" name="max_kg" value="0">
                            </div>

                            <div class="form-group">
                                <label for="rep_number">{{ __('Repetition Number') }}</label>
                                <input type="text" class="form-control" id="rep_number" name="rep_number" value="5">
                            </div>

                            <div class="form-group">
                                <label for="tara">{{ __('Tara') }}</label>
                                <input type="text" class="form-control" id="tara" name="tara" value="0">
                            </div>

                            <div class="form-group">
                                <label for="tara_calibrate">{{ __('Tara Calibrate') }}</label>
                                <input type="text" class="form-control" id="tara_calibrate" name="tara_calibrate" value="0">
                            </div>

                            <div class="form-group">
                                <label for="calibration_type">{{ __('Calibration Type') }}</label>
                                <select class="form-control" id="calibration_type" name="calibration_type" required>
                                    <option value="none">Sin Tara</option>
                                    <option value="software">Tara por Software</option>
                                    <option value="hardware">Tara por Hardware</option>
                                </select>
                            </div>

                            <div class="form-group">
                                <label for="min_kg">{{ __('Min KG') }}</label>
                                <input type="text" class="form-control" id="min_kg" name="min_kg" value="0.10">
                            </div>

                            <div class="form-group">
                                <label for="last_kg">{{ __('Last KG') }}</label>
                                <input type="text" class="form-control" id="last_kg" name="last_kg" value="0">
                            </div>

                            <div class="form-group">
                                <label for="last_rep">{{ __('Last Repetition') }}</label>
                                <input type="text" class="form-control" id="last_rep" name="last_rep" value="0">
                            </div>

                            <div class="form-group">
                                <label for="rec_box">{{ __('Rec Box') }}</label>
                                <input type="text" class="form-control" id="rec_box" name="rec_box" value="0">
                            </div>

                            <div class="form-group">
                                <label for="rec_box_shift">{{ __('Rec Box Shift') }}</label>
                                <input type="text" class="form-control" id="rec_box_shift" name="rec_box_shift" value="0">
                            </div>

                            <div class="form-group">
                                <label for="rec_box_unlimited">{{ __('Rec Box Unlimited') }}</label>
                                <input type="text" class="form-control" id="rec_box_unlimited" name="rec_box_unlimited" value="0">
                            </div>

                            <div class="form-group">
                                <label for="last_value">{{ __('Last Value') }}</label>
                                <input type="text" class="form-control" id="last_value" name="last_value" value="0">
                            </div>

                            <div class="form-group">
                                <label for="variacion_number">{{ __('Variation Number') }}</label>
                                <input type="text" class="form-control" id="variacion_number" name="variacion_number" value="0.01">
                            </div>

                            <div class="form-group">
                                <label for="model_name">{{ __('Model Name') }}</label>
                                <select class="form-control" id="model_name" name="model_name" required>
                                    <option value="weight">Weight</option>
                                    <option value="height">Height</option>
                                    <option value="lifeTraficMonitor">Life Traffic Monitor</option>
                                </select>
                            </div>

                            <div class="form-group">
                                <label for="dimension_default">{{ __('Dimension Default') }}</label>
                                <input type="text" class="form-control" id="dimension_default" name="dimension_default" value="30000">
                            </div>

                            <div class="form-group">
                                <label for="dimension_max">{{ __('Dimension Max') }}</label>
                                <input type="text" class="form-control" id="dimension_max" name="dimension_max">
                            </div>

                            <div class="form-group">
                                <label for="dimension_variacion">{{ __('Dimension Variation') }}</label>
                                <input type="text" class="form-control" id="dimension_variacion" name="dimension_variacion" value="20">
                            </div>

                            <div class="form-group">
                                <label for="offset_meter">{{ __('Offset Meter') }}</label>
                                <input type="text" class="form-control" id="offset_meter" name="offset_meter" value="0">
                            </div>

                            <div class="form-group">
                                <label for="printer_id">{{ __('Printer ID') }}</label>
                                <input type="text" class="form-control" id="printer_id" name="printer_id">
                            </div>

                            <div class="form-group">
                                <label for="unic_code_order">{{ __('Unic Code Order') }}</label>
                                <input type="text" class="form-control" id="unic_code_order" name="unic_code_order" value="{{ uniqid() }}">
                            </div>

                            <div class="form-group">
                                <label for="shift_type">{{ __('Shift Type') }}</label>
                                <input type="text" class="form-control" id="shift_type" name="shift_type"  value="shift">
                            </div>

                            <div class="form-group">
                                <label for="event">{{ __('Event') }}</label>
                                <input type="text" class="form-control" id="event" name="event" value="start">
                            </div>

                            <div class="form-group">
                                <label for="downtime_count">{{ __('Downtime Count') }}</label>
                                <input type="number" class="form-control" id="downtime_count" name="downtime_count" value="0">
                            </div>

                            <div class="form-group">
                                <label for="barcoder_id">{{ __('Barcoder ID') }}</label>
                                <input type="text" class="form-control" id="barcoder_id" name="barcoder_id">
                            </div>

                            <div class="form-group">
                                <label for="box_type">{{ __('Box Type') }}</label>
                                <input type="text" class="form-control" id="box_type" name="box_type" value="Caja/s">
                            </div>

                            <div class="form-group">
                                <label for="group">{{ __('Group') }}</label>
                                <input type="number" class="form-control" id="group" name="group" value="0">
                            </div>
                            <div class="form-group">
                                <label for="model_type">{{ __('Model Type') }}</label>
                                <select class="form-control" id="model_type" name="model_type">
                                    <option value="0">{{ __('Conteo') }}</option>
                                    <option value="1">{{ __('Materia Prima') }} 1</option>
                                    <option value="2">{{ __('Materia Prima') }} 2</option>
                                    <option value="3">{{ __('Materia Prima') }} 3</option>
                                    <option value="4">{{ __('Materia Prima') }} 4</option>
                                </select>
                            </div>                            

                            <div class="form-group">
                                <label for="box_width">{{ __('Box Width') }}</label>
                                <input type="number" class="form-control" id="box_width" name="box_width">
                            </div>

                            <div class="form-group">
                                <label for="box_length">{{ __('Box Length') }}</label>
                                <input type="number" class="form-control" id="box_length" name="box_length">
                            </div>

                            <!-- Campo nuevo: is_material_receiver -->
                            <div class="form-group">
                                <label for="is_material_receiver">{{ __('Is Material Receiver') }}</label>
                                <select class="form-control" id="is_material_receiver" name="is_material_receiver" required>
                                    <option value="0" {{ $modbus->is_material_receiver == false ? 'selected' : '' }}>No</option>
                                    <option value="1" {{ $modbus->is_material_receiver == true ? 'selected' : '' }}>Yes</option>
                                </select>
                            </div>
                            <button type="submit" class="btn btn-primary">{{ __('Save') }}</button>
                            <a href="{{ route('modbuses.index', $production_line_id) }}" class="btn btn-secondary">{{ __('Cancel') }}</a>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection