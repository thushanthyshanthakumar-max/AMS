-- Add sample partitions for demonstration
-- Morning Session: 9:00 AM - 11:00 AM
-- Evening Session: 1:00 PM - 3:00 PM

INSERT INTO
    partitions (
        group_id,
        name,
        start_time,
        end_time,
        day_of_week
    )
VALUES
    -- GROUP-01 partitions
    (
        1,
        'Morning Session',
        '09:00:00',
        '11:00:00',
        'Monday'
    ),
    (
        1,
        'Evening Session',
        '13:00:00',
        '15:00:00',
        'Monday'
    ),
    (
        1,
        'Morning Session',
        '09:00:00',
        '11:00:00',
        'Tuesday'
    ),
    (
        1,
        'Evening Session',
        '13:00:00',
        '15:00:00',
        'Tuesday'
    ),
    (
        1,
        'Morning Session',
        '09:00:00',
        '11:00:00',
        'Wednesday'
    ),
    (
        1,
        'Evening Session',
        '13:00:00',
        '15:00:00',
        'Wednesday'
    ),
    (
        1,
        'Morning Session',
        '09:00:00',
        '11:00:00',
        'Thursday'
    ),
    (
        1,
        'Evening Session',
        '13:00:00',
        '15:00:00',
        'Thursday'
    ),
    (
        1,
        'Morning Session',
        '09:00:00',
        '11:00:00',
        'Friday'
    ),
    (
        1,
        'Evening Session',
        '13:00:00',
        '15:00:00',
        'Friday'
    ),

-- GROUP-02 partitions
(
    2,
    'Morning Session',
    '09:00:00',
    '11:00:00',
    'Monday'
),
(
    2,
    'Evening Session',
    '13:00:00',
    '15:00:00',
    'Monday'
),
(
    2,
    'Morning Session',
    '09:00:00',
    '11:00:00',
    'Tuesday'
),
(
    2,
    'Evening Session',
    '13:00:00',
    '15:00:00',
    'Tuesday'
),
(
    2,
    'Morning Session',
    '09:00:00',
    '11:00:00',
    'Wednesday'
),
(
    2,
    'Evening Session',
    '13:00:00',
    '15:00:00',
    'Wednesday'
),
(
    2,
    'Morning Session',
    '09:00:00',
    '11:00:00',
    'Thursday'
),
(
    2,
    'Evening Session',
    '13:00:00',
    '15:00:00',
    'Thursday'
),
(
    2,
    'Morning Session',
    '09:00:00',
    '11:00:00',
    'Friday'
),
(
    2,
    'Evening Session',
    '13:00:00',
    '15:00:00',
    'Friday'
),

-- GROUP-03 partitions
(
    3,
    'Morning Session',
    '09:00:00',
    '11:00:00',
    'Monday'
),
(
    3,
    'Evening Session',
    '13:00:00',
    '15:00:00',
    'Monday'
),
(
    3,
    'Morning Session',
    '09:00:00',
    '11:00:00',
    'Tuesday'
),
(
    3,
    'Evening Session',
    '13:00:00',
    '15:00:00',
    'Tuesday'
),
(
    3,
    'Morning Session',
    '09:00:00',
    '11:00:00',
    'Wednesday'
),
(
    3,
    'Evening Session',
    '13:00:00',
    '15:00:00',
    'Wednesday'
),
(
    3,
    'Morning Session',
    '09:00:00',
    '11:00:00',
    'Thursday'
),
(
    3,
    'Evening Session',
    '13:00:00',
    '15:00:00',
    'Thursday'
),
(
    3,
    'Morning Session',
    '09:00:00',
    '11:00:00',
    'Friday'
),
(
    3,
    'Evening Session',
    '13:00:00',
    '15:00:00',
    'Friday'
),

-- GROUP-04 partitions
(
    4,
    'Morning Session',
    '09:00:00',
    '11:00:00',
    'Monday'
),
(
    4,
    'Evening Session',
    '13:00:00',
    '15:00:00',
    'Monday'
),
(
    4,
    'Morning Session',
    '09:00:00',
    '11:00:00',
    'Tuesday'
),
(
    4,
    'Evening Session',
    '13:00:00',
    '15:00:00',
    'Tuesday'
),
(
    4,
    'Morning Session',
    '09:00:00',
    '11:00:00',
    'Wednesday'
),
(
    4,
    'Evening Session',
    '13:00:00',
    '15:00:00',
    'Wednesday'
),
(
    4,
    'Morning Session',
    '09:00:00',
    '11:00:00',
    'Thursday'
),
(
    4,
    'Evening Session',
    '13:00:00',
    '15:00:00',
    'Thursday'
),
(
    4,
    'Morning Session',
    '09:00:00',
    '11:00:00',
    'Friday'
),
(
    4,
    'Evening Session',
    '13:00:00',
    '15:00:00',
    'Friday'
),

-- GROUP-05 partitions
(
    5,
    'Morning Session',
    '09:00:00',
    '11:00:00',
    'Monday'
),
(
    5,
    'Evening Session',
    '13:00:00',
    '15:00:00',
    'Monday'
),
(
    5,
    'Morning Session',
    '09:00:00',
    '11:00:00',
    'Tuesday'
),
(
    5,
    'Evening Session',
    '13:00:00',
    '15:00:00',
    'Tuesday'
),
(
    5,
    'Morning Session',
    '09:00:00',
    '11:00:00',
    'Wednesday'
),
(
    5,
    'Evening Session',
    '13:00:00',
    '15:00:00',
    'Wednesday'
),
(
    5,
    'Morning Session',
    '09:00:00',
    '11:00:00',
    'Thursday'
),
(
    5,
    'Evening Session',
    '13:00:00',
    '15:00:00',
    'Thursday'
),
(
    5,
    'Morning Session',
    '09:00:00',
    '11:00:00',
    'Friday'
),
(
    5,
    'Evening Session',
    '13:00:00',
    '15:00:00',
    'Friday'
),

-- GROUP-06 partitions
(
    6,
    'Morning Session',
    '09:00:00',
    '11:00:00',
    'Monday'
),
(
    6,
    'Evening Session',
    '13:00:00',
    '15:00:00',
    'Monday'
),
(
    6,
    'Morning Session',
    '09:00:00',
    '11:00:00',
    'Tuesday'
),
(
    6,
    'Evening Session',
    '13:00:00',
    '15:00:00',
    'Tuesday'
),
(
    6,
    'Morning Session',
    '09:00:00',
    '11:00:00',
    'Wednesday'
),
(
    6,
    'Evening Session',
    '13:00:00',
    '15:00:00',
    'Wednesday'
),
(
    6,
    'Morning Session',
    '09:00:00',
    '11:00:00',
    'Thursday'
),
(
    6,
    'Evening Session',
    '13:00:00',
    '15:00:00',
    'Thursday'
),
(
    6,
    'Morning Session',
    '09:00:00',
    '11:00:00',
    'Friday'
),
(
    6,
    'Evening Session',
    '13:00:00',
    '15:00:00',
    'Friday'
),

-- GROUP-07 partitions
(
    7,
    'Morning Session',
    '09:00:00',
    '11:00:00',
    'Monday'
),
(
    7,
    'Evening Session',
    '13:00:00',
    '15:00:00',
    'Monday'
),
(
    7,
    'Morning Session',
    '09:00:00',
    '11:00:00',
    'Tuesday'
),
(
    7,
    'Evening Session',
    '13:00:00',
    '15:00:00',
    'Tuesday'
),
(
    7,
    'Morning Session',
    '09:00:00',
    '11:00:00',
    'Wednesday'
),
(
    7,
    'Evening Session',
    '13:00:00',
    '15:00:00',
    'Wednesday'
),
(
    7,
    'Morning Session',
    '09:00:00',
    '11:00:00',
    'Thursday'
),
(
    7,
    'Evening Session',
    '13:00:00',
    '15:00:00',
    'Thursday'
),
(
    7,
    'Morning Session',
    '09:00:00',
    '11:00:00',
    'Friday'
),
(
    7,
    'Evening Session',
    '13:00:00',
    '15:00:00',
    'Friday'
),

-- GROUP-08 partitions
(
    8,
    'Morning Session',
    '09:00:00',
    '11:00:00',
    'Monday'
),
(
    8,
    'Evening Session',
    '13:00:00',
    '15:00:00',
    'Monday'
),
(
    8,
    'Morning Session',
    '09:00:00',
    '11:00:00',
    'Tuesday'
),
(
    8,
    'Evening Session',
    '13:00:00',
    '15:00:00',
    'Tuesday'
),
(
    8,
    'Morning Session',
    '09:00:00',
    '11:00:00',
    'Wednesday'
),
(
    8,
    'Evening Session',
    '13:00:00',
    '15:00:00',
    'Wednesday'
),
(
    8,
    'Morning Session',
    '09:00:00',
    '11:00:00',
    'Thursday'
),
(
    8,
    'Evening Session',
    '13:00:00',
    '15:00:00',
    'Thursday'
),
(
    8,
    'Morning Session',
    '09:00:00',
    '11:00:00',
    'Friday'
),
(
    8,
    'Evening Session',
    '13:00:00',
    '15:00:00',
    'Friday'
),

-- GROUP-09 partitions
(
    9,
    'Morning Session',
    '09:00:00',
    '11:00:00',
    'Monday'
),
(
    9,
    'Evening Session',
    '13:00:00',
    '15:00:00',
    'Monday'
),
(
    9,
    'Morning Session',
    '09:00:00',
    '11:00:00',
    'Tuesday'
),
(
    9,
    'Evening Session',
    '13:00:00',
    '15:00:00',
    'Tuesday'
),
(
    9,
    'Morning Session',
    '09:00:00',
    '11:00:00',
    'Wednesday'
),
(
    9,
    'Evening Session',
    '13:00:00',
    '15:00:00',
    'Wednesday'
),
(
    9,
    'Morning Session',
    '09:00:00',
    '11:00:00',
    'Thursday'
),
(
    9,
    'Evening Session',
    '13:00:00',
    '15:00:00',
    'Thursday'
),
(
    9,
    'Morning Session',
    '09:00:00',
    '11:00:00',
    'Friday'
),
(
    9,
    'Evening Session',
    '13:00:00',
    '15:00:00',
    'Friday'
),

-- GROUP-10 partitions
(
    10,
    'Morning Session',
    '09:00:00',
    '11:00:00',
    'Monday'
),
(
    10,
    'Evening Session',
    '13:00:00',
    '15:00:00',
    'Monday'
),
(
    10,
    'Morning Session',
    '09:00:00',
    '11:00:00',
    'Tuesday'
),
(
    10,
    'Evening Session',
    '13:00:00',
    '15:00:00',
    'Tuesday'
),
(
    10,
    'Morning Session',
    '09:00:00',
    '11:00:00',
    'Wednesday'
),
(
    10,
    'Evening Session',
    '13:00:00',
    '15:00:00',
    'Wednesday'
),
(
    10,
    'Morning Session',
    '09:00:00',
    '11:00:00',
    'Thursday'
),
(
    10,
    'Evening Session',
    '13:00:00',
    '15:00:00',
    'Thursday'
),
(
    10,
    'Morning Session',
    '09:00:00',
    '11:00:00',
    'Friday'
),
(
    10,
    'Evening Session',
    '13:00:00',
    '15:00:00',
    'Friday'
);