/**
 * Partitions Management JavaScript
 * Handles dynamic partition field addition
 */

let partitionCount = 1;

function addPartitionField() {
    const container = document.getElementById('partitionsContainer');
    const daysOfWeek = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];

    const partitionDiv = document.createElement('div');
    partitionDiv.className = 'partition-item';
    partitionDiv.style.marginTop = '1rem';
    partitionDiv.style.paddingTop = '1rem';
    partitionDiv.style.borderTop = '2px solid var(--border-color)';

    let daysOptions = '<option value="">Day</option>';
    daysOfWeek.forEach(day => {
        daysOptions += `<option value="${day}">${day}</option>`;
    });

    partitionDiv.innerHTML = `
        <div style="display: grid; grid-template-columns: 2fr 1fr 1fr 1fr 50px; gap: 1rem; align-items: end;">
            <div class="form-group">
                <label class="form-label">Partition Name</label>
                <input type="text" name="partitions[${partitionCount}][name]" class="form-control" placeholder="e.g., Evening Class" required>
            </div>
            <div class="form-group">
                <label class="form-label">Start Time</label>
                <input type="time" name="partitions[${partitionCount}][start_time]" class="form-control" required>
            </div>
            <div class="form-group">
                <label class="form-label">End Time</label>
                <input type="time" name="partitions[${partitionCount}][end_time]" class="form-control" required>
            </div>
            <div class="form-group">
                <label class="form-label">Day</label>
                <select name="partitions[${partitionCount}][day_of_week]" class="form-control" required>
                    ${daysOptions}
                </select>
            </div>
            <div>
                <button type="button" class="btn btn-sm btn-danger" onclick="removePartitionField(this)" style="padding: 0.5rem;">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        </div>
    `;

    container.appendChild(partitionDiv);
    partitionCount++;
}

function removePartitionField(button) {
    const partitionItem = button.closest('.partition-item');
    partitionItem.remove();
}

function editPartition(id, name, startTime, endTime, dayOfWeek) {
    document.getElementById('edit_id').value = id;
    document.getElementById('edit_name').value = name;
    document.getElementById('edit_start_time').value = startTime;
    document.getElementById('edit_end_time').value = endTime;
    document.getElementById('edit_day_of_week').value = dayOfWeek;
    openModal('editPartitionModal');
}
