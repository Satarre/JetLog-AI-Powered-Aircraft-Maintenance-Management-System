-- JetLog demo data. All records are simulated.
-- Login: tech / jetlog123

USE jetlog;

INSERT INTO users (id, username, password_hash, full_name, role) VALUES
(1, 'tech', '$2y$10$M1GTyGlWReMGvy2g5DX7wu56ejQQFctp/NObtSPUGg0VfDEo5.N6i', 'Demo Technician', 'technician'),
(2, 'msantos', '$2y$10$M1GTyGlWReMGvy2g5DX7wu56ejQQFctp/NObtSPUGg0VfDEo5.N6i', 'Maria Santos', 'technician'),
(3, 'jreyes', '$2y$10$M1GTyGlWReMGvy2g5DX7wu56ejQQFctp/NObtSPUGg0VfDEo5.N6i', 'Joel Reyes', 'supervisor');

INSERT INTO aircraft (id, registration, aircraft_type, msn, base, status, total_hours) VALUES
(1, 'RP-C4101', 'Airbus A330-300', '1901', 'MNL', 'in_service', 18456.0),
(2, 'RP-C4102', 'Airbus A330-300', '1902', 'MNL', 'in_service', 19228.5),
(3, 'RP-C4103', 'Boeing 777-300ER', '42011', 'MNL', 'maintenance', 26320.0),
(4, 'RP-C4104', 'Airbus A350-900', '33045', 'CEB', 'in_service', 9845.0),
(5, 'RP-C4105', 'Airbus A350-900', '33046', 'CEB', 'maintenance', 10224.0),
(6, 'RP-C4106', 'Airbus A321neo', '11020', 'CRK', 'in_service', 4215.0),
(7, 'RP-C4107', 'Boeing 777-300ER', '42012', 'MNL', 'grounded', 27185.0);

INSERT INTO discrepancies (id, aircraft_id, reported_by, title, description, severity, status, reported_at, closed_at) VALUES
(1, 1, 1, 'Left engine oil pressure fluctuation', 'Oil pressure indication moved between 58 and 66 psi during climb. Recorded for check.', 'high', 'open', DATE_SUB(NOW(), INTERVAL 2 DAY), NULL),
(2, 1, 2, 'Cabin door 2L seal wear', 'Visible wear on the lower door seal. No leak found during pressurization check.', 'low', 'closed', DATE_SUB(NOW(), INTERVAL 20 DAY), DATE_SUB(NOW(), INTERVAL 18 DAY)),
(3, 3, 1, 'APU exhaust temperature high', 'APU exhaust temperature reached 690 C during the last run. Unit was shut down.', 'high', 'in_progress', DATE_SUB(NOW(), INTERVAL 5 DAY), NULL),
(4, 3, 3, 'Nose gear steering noise', 'Crew reported a knocking sound during taxi turns. Not reproduced on the ground.', 'medium', 'open', DATE_SUB(NOW(), INTERVAL 3 DAY), NULL),
(5, 4, 2, 'Cabin reading light flicker', 'Reading light at seat 32A flickers when dimmed. Unit reported by cabin crew.', 'low', 'closed', DATE_SUB(NOW(), INTERVAL 6 DAY), DATE_SUB(NOW(), INTERVAL 4 DAY)),
(6, 5, 1, 'Hydraulic leak at right main gear', 'Small hydraulic seep found near the right main gear actuator. Area cleaned and monitored.', 'high', 'in_progress', DATE_SUB(NOW(), INTERVAL 4 DAY), NULL),
(7, 5, 2, 'Weather radar display intermittent', 'Radar display blanked for about two seconds twice during approach. Unit reset once.', 'medium', 'open', DATE_SUB(NOW(), INTERVAL 1 DAY), NULL),
(8, 7, 3, 'Engine 2 vibration above limit', 'Vibration reading reached 4.2 units during run. Aircraft remains grounded.', 'high', 'open', DATE_SUB(NOW(), INTERVAL 7 DAY), NULL),
(9, 6, 1, 'Cargo door warning sensor fault', 'Cargo door warning showed open with door closed. Sensor replaced.', 'medium', 'closed', DATE_SUB(NOW(), INTERVAL 30 DAY), DATE_SUB(NOW(), INTERVAL 25 DAY));

INSERT INTO work_orders (id, wo_number, aircraft_id, discrepancy_id, assigned_to, created_by, title, description, priority, status, opened_at, completed_at) VALUES
(1, 'WO-0001', 3, 3, 1, 3, 'APU exhaust temperature check', 'Inspect APU exhaust and record temperatures during a controlled run.', 'high', 'in_progress', DATE_SUB(NOW(), INTERVAL 5 DAY), NULL),
(2, 'WO-0002', 5, 6, 2, 3, 'Right main gear hydraulic line replacement', 'Replace the affected hydraulic line and perform a leak check.', 'high', 'open', DATE_SUB(NOW(), INTERVAL 4 DAY), NULL),
(3, 'WO-0003', 7, 8, NULL, 2, 'Engine 2 vibration inspection', 'Inspect engine 2 mounts and fan blades. Record vibration readings.', 'high', 'open', DATE_SUB(NOW(), INTERVAL 7 DAY), NULL),
(4, 'WO-0004', 1, 1, 1, 3, 'Left engine oil pressure check', 'Check sensor connection and record oil pressure during ground run.', 'medium', 'in_progress', DATE_SUB(NOW(), INTERVAL 2 DAY), NULL),
(5, 'WO-0005', 4, 5, 2, 1, 'Cabin reading light repair', 'Replace the reading light unit at seat 32A.', 'low', 'completed', DATE_SUB(NOW(), INTERVAL 8 DAY), DATE_SUB(NOW(), INTERVAL 4 DAY)),
(6, 'WO-0006', 6, 9, 1, 2, 'Cargo door sensor replacement', 'Replace the cargo door warning sensor and test the indication.', 'medium', 'completed', DATE_SUB(NOW(), INTERVAL 27 DAY), DATE_SUB(NOW(), INTERVAL 25 DAY)),
(7, 'WO-0007', 1, 2, 2, 3, 'Cabin door 2L seal replacement', 'Replace the cabin door 2L lower seal and perform a pressurization check.', 'low', 'completed', DATE_SUB(NOW(), INTERVAL 21 DAY), DATE_SUB(NOW(), INTERVAL 18 DAY));

INSERT INTO maintenance_schedule (id, aircraft_id, task_code, title, task_type, interval_days, last_done_date, next_due_date, notes) VALUES
(1, 1, 'A-CHECK', 'A-check', 'check', 400, DATE_SUB(CURDATE(), INTERVAL 350 DAY), DATE_ADD(CURDATE(), INTERVAL 50 DAY), 'Package 1 and 2.'),
(2, 1, 'WK-WALK', 'Weekly walkaround', 'inspection', 7, DATE_SUB(CURDATE(), INTERVAL 6 DAY), DATE_ADD(CURDATE(), INTERVAL 1 DAY), NULL),
(3, 1, 'ELT-BATT', 'ELT battery inspection', 'inspection', 365, DATE_SUB(CURDATE(), INTERVAL 370 DAY), DATE_SUB(CURDATE(), INTERVAL 5 DAY), 'Battery due for replacement.'),
(4, 2, 'A-CHECK', 'A-check', 'check', 400, DATE_SUB(CURDATE(), INTERVAL 200 DAY), DATE_ADD(CURDATE(), INTERVAL 200 DAY), NULL),
(5, 2, 'WK-WALK', 'Weekly walkaround', 'inspection', 7, DATE_SUB(CURDATE(), INTERVAL 3 DAY), DATE_ADD(CURDATE(), INTERVAL 4 DAY), NULL),
(6, 3, 'C-CHECK', 'C-check', 'check', 730, DATE_SUB(CURDATE(), INTERVAL 700 DAY), DATE_ADD(CURDATE(), INTERVAL 30 DAY), 'Hangar slot requested.'),
(7, 3, 'GEAR-INSP', 'Landing gear inspection', 'inspection', 180, DATE_SUB(CURDATE(), INTERVAL 185 DAY), DATE_SUB(CURDATE(), INTERVAL 5 DAY), NULL),
(8, 3, 'WK-WALK', 'Weekly walkaround', 'inspection', 7, DATE_SUB(CURDATE(), INTERVAL 7 DAY), CURDATE(), NULL),
(9, 4, 'A-CHECK', 'A-check', 'check', 400, DATE_SUB(CURDATE(), INTERVAL 100 DAY), DATE_ADD(CURDATE(), INTERVAL 300 DAY), NULL),
(10, 4, 'WK-WALK', 'Weekly walkaround', 'inspection', 7, DATE_SUB(CURDATE(), INTERVAL 2 DAY), DATE_ADD(CURDATE(), INTERVAL 5 DAY), NULL),
(11, 5, 'A-CHECK', 'A-check', 'check', 400, DATE_SUB(CURDATE(), INTERVAL 390 DAY), DATE_ADD(CURDATE(), INTERVAL 10 DAY), NULL),
(12, 5, 'HYD-CHECK', 'Hydraulic system check', 'inspection', 90, DATE_SUB(CURDATE(), INTERVAL 95 DAY), DATE_SUB(CURDATE(), INTERVAL 5 DAY), 'Linked to open hydraulic discrepancy.'),
(13, 6, 'A-CHECK', 'A-check', 'check', 400, DATE_SUB(CURDATE(), INTERVAL 30 DAY), DATE_ADD(CURDATE(), INTERVAL 370 DAY), NULL),
(14, 7, 'ENG-VIB', 'Engine vibration check', 'inspection', 30, DATE_SUB(CURDATE(), INTERVAL 35 DAY), DATE_SUB(CURDATE(), INTERVAL 5 DAY), 'Aircraft grounded.'),
(15, 7, 'WK-WALK', 'Weekly walkaround', 'inspection', 7, DATE_SUB(CURDATE(), INTERVAL 10 DAY), DATE_SUB(CURDATE(), INTERVAL 3 DAY), NULL);

INSERT INTO logbook_entries (id, aircraft_id, work_order_id, created_by, entry_date, entry_type, description, performed_by, flight_hours) VALUES
(1, 1, 7, 2, DATE_SUB(CURDATE(), INTERVAL 18 DAY), 'repair', 'Replaced cabin door 2L lower seal. Pressurization check passed.', 'Maria Santos', 18450.5),
(2, 1, NULL, 1, DATE_SUB(CURDATE(), INTERVAL 16 DAY), 'inspection', 'Weekly walkaround completed. No defects found.', 'Demo Technician', 18452.0),
(3, 1, 4, 1, DATE_SUB(CURDATE(), INTERVAL 1 DAY), 'note', 'Oil pressure sensor connection cleaned. Ground run scheduled.', 'Demo Technician', 18456.0),
(4, 2, NULL, 2, DATE_SUB(CURDATE(), INTERVAL 12 DAY), 'inspection', 'A-check package 1 completed. No findings.', 'Maria Santos', 19220.0),
(5, 3, 1, 1, DATE_SUB(CURDATE(), INTERVAL 5 DAY), 'note', 'APU exhaust inspected. Two controlled runs recorded.', 'Demo Technician', 26314.0),
(6, 3, NULL, 3, DATE_SUB(CURDATE(), INTERVAL 4 DAY), 'inspection', 'Weekly walkaround completed. Nose gear noise noted by crew.', 'Joel Reyes', 26320.0),
(7, 4, 5, 2, DATE_SUB(CURDATE(), INTERVAL 4 DAY), 'repair', 'Replaced reading light unit at seat 32A. Function check passed.', 'Maria Santos', 9842.0),
(8, 4, NULL, 1, DATE_SUB(CURDATE(), INTERVAL 2 DAY), 'inspection', 'Weekly walkaround completed. No defects found.', 'Demo Technician', 9845.0),
(9, 5, NULL, 1, DATE_SUB(CURDATE(), INTERVAL 3 DAY), 'note', 'Hydraulic seep isolated to the right main gear line. Area cleaned.', 'Demo Technician', 10220.0),
(10, 5, NULL, 2, DATE_SUB(CURDATE(), INTERVAL 2 DAY), 'inspection', 'A-check package 2 completed. Hydraulic check remains open.', 'Maria Santos', 10222.0),
(11, 5, NULL, 2, DATE_SUB(CURDATE(), INTERVAL 1 DAY), 'note', 'Weather radar display fault recorded. Unit reset once.', 'Maria Santos', 10224.0),
(12, 6, 6, 1, DATE_SUB(CURDATE(), INTERVAL 25 DAY), 'component', 'Replaced cargo door warning sensor. Indication test passed.', 'Demo Technician', 4210.0),
(13, 6, NULL, 3, DATE_SUB(CURDATE(), INTERVAL 10 DAY), 'inspection', 'A-check package 3 completed. No findings.', 'Joel Reyes', 4215.0),
(14, 7, NULL, 3, DATE_SUB(CURDATE(), INTERVAL 6 DAY), 'note', 'Engine 2 vibration reading recorded during run. Aircraft grounded.', 'Joel Reyes', 27185.0),
(15, 7, NULL, 1, DATE_SUB(CURDATE(), INTERVAL 3 DAY), 'inspection', 'Weekly walkaround completed. Aircraft remains grounded.', 'Demo Technician', 27185.0),
(16, 2, NULL, 1, DATE_SUB(CURDATE(), INTERVAL 1 DAY), 'note', 'Transit check completed. Aircraft released to service.', 'Demo Technician', 19228.5);
