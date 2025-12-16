<!-- Event Modal -->
<div id="eventModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h2>Create Event</h2>
            <span class="closeModal">&times;</span>
        </div>
        <div class="modal-body">
            <form id="eventForm">
                @csrf
                
                <!-- Choose event type -->
                <div class="form-group">
                    <label for="eventTypeSelect">Event type</label>

                    <select id="eventTypeSelect">
                        <option value="meeting">Meeting</option>
                        <option value="event">Event</option>
                        <option value="maintenance">Maintenance</option>
                    </select>
                </div>


                    <!-- Choose date -->
                <div class="form-group">
                    <div>
                        <label for="meeting-date">Meeting date *</label>
                        <input type="date" id="meeting-date" name="meeting-date" required>
                    </div>
                </div>

                <!-- Choose time -->
                <div class="form-group date-time ">
                    <div>
                        <label for="meeting-time-from">Time from *</label>
                        <input type="time" id="meeting-time-from" name="meeting-time-from" required>
                    </div>
                    <div>
                        <label for="meeting-time-to">Time to*</label>
                        <input type="time" id="meeting-time-to" name="meeting-time-to" required>
                    </div>
                </div>

                <!-- Choose attendees -->
                 <div class="form-group">
                    <label for="userSelect">Assign users</label>
                    
                    <select id="userSelect">
                        <option value="">- Select user -</option>
                        @foreach ($users as $user)
                            <option value="{{ $user->id }}">
                                {{ $user->name }}
                            </option>
                        @endforeach
                    </select>
                 </div>

                <!-- Choose desks -->
                <div class="form-group">
                    <label for="miniLayout">Select desks for the meeting</label>
                    <div id="miniLayout" class="mini-layout"></div>
                </div>

                <!-- Description -->
                <div class="form-group">
                    <label for="eventFormDescription">Description *</label>
                    <textarea id="eventFormDescription" name="eventFormDescription" rows="5" placeholder="Describe the purpose of the meeting" required></textarea>
                </div>

                <button type="submit" class="formButton">Send request</button>
            </form>
        </div>
    </div>
</div>


<!-- Cleaning Schedule Modal -->
<div id="cleaningModal" class="modal">
    <div class="cleaning-modal-content">
        <div class="modal-header">
            <h2>Cleaning Schedule</h2>
            <span class="closeModal">&times;</span>
        </div>
        <div class="modal-body">
            <form id="cleaningForm">
                <div class="vertical-alignment">
                    @csrf

                    <!-- Choose time -->
                        
                    <div class="form-group cleaningTime">
                        <input type="time" id="cleaningTime" name="cleaningTime" value="{{ now()->format('H:i') }}" required>
                    </div>
                
                    <!-- Choose days -->
                     <div class="cleaningAllDays">
                        <label for="scheduleDays">Repeat:</label>

                        @php 
                            $days = [
                                'MON' => 'M',
                                'TUE' => 'T',
                                'WED' => 'W',
                                'THU' => 'T',
                                'FRI' => 'F',
                                'SAT' => 'S',
                                'SUN' => 'S',
                                ];
                        @endphp

                        @foreach ($days as $day => $label)
                            <button 
                                type="button"
                                class="btn scheduleDay {{ in_array($day, $recurringCleaningDays, true) ? 'active' : '' }}"
                                data-day="{{ $day }}"> 
                                    {{ $label }}
                            </button>
                        @endforeach
                                
                     </div>


                    <!-- Submit/Finish button -->        
                    <button type="submit" class="formButton">New Schedule</button>
                </div>
            </form>
        </div>
    </div>
</div>