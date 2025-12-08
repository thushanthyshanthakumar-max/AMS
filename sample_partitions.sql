-- Add sample partitions for demonstration
-- Morning Session: 9:00 AM - 11:00 AM
-- Evening Session: 1:00 PM - 3:00 PM

INSERT INTO partitions (group_id, name, start_time, end_time, day_of_week) VALUES
-- GROUP-01 partitions
(1, 'Morning Session', '09:00:00', '11:00:00', 'Monday'),
(1, 'Evening Session', '13:00:00', '15:00:00', 'Monday'),
(1, 'Morning Session', '09:00:00', '11:00:00', 'Wednesday'),
(1, 'Evening Session', '13:00:00', '15:00:00', 'Wednesday'),
(1, 'Morning Session', '09:00:00', '11:00:00', 'Friday'),

-- GROUP-02 partitions
(2, 'Morning Session', '09:00:00', '11:00:00', 'Tuesday'),
(2, 'Evening Session', '13:00:00', '15:00:00', 'Tuesday'),
(2, 'Morning Session', '09:00:00', '11:00:00', 'Thursday'),

-- GROUP-03 partitions
(3, 'Morning Session', '09:00:00', '11:00:00', 'Monday'),
(3, 'Evening Session', '13:00:00', '15:00:00', 'Wednesday'),

-- GROUP-04 partitions
(4, 'Morning Session', '09:00:00', '11:00:00', 'Tuesday'),
(4, 'Evening Session', '13:00:00', '15:00:00', 'Thursday'),

-- GROUP-05 partitions
(5, 'Morning Session', '09:00:00', '11:00:00', 'Friday'),
(5, 'Evening Session', '13:00:00', '15:00:00', 'Friday');
