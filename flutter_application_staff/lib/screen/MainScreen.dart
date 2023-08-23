import 'package:flutter/material.dart';
import 'package:flutter_application_staff/models/Staff.dart';
import 'package:flutter_application_staff/Data.dart';
import 'package:qr_code_scanner/qr_code_scanner.dart';
import 'CheckinScreen.dart';
import 'StaffInfor.dart';
import 'ScheduleScreen.dart';
import 'dart:core';

import 'Login.dart';

class MainScreen extends StatefulWidget {
  final Staff staff;
  final BuildContext context;
  const MainScreen({Key? key, required this.staff, required this.context})
      : super(key: key);
  @override
  State<MainScreen> createState() => _MainScreenState();
}

class _MainScreenState extends State<MainScreen> {
  late Staff staff1;
  BuildContext? context1;
  List<Widget>? screens;
  @override
  void initState() {
    super.initState();
    staff1 = widget.staff;
    context1 = widget.context;
    screens = [
      ScheduleScreen(staff: staff1,),
      AttendanceScreen(),
      StaffInforScreen(
        staff: staff1,
        context: context1,
      )
    ];
  }

  int _selectedIndex = 0;

  @override
  Widget build(BuildContext context) {
    // TODO: implement build
    return Scaffold(
      body: screens![_selectedIndex],
      bottomNavigationBar: BottomNavigationBar(
        currentIndex: _selectedIndex,
        onTap: (int index) {
          setState(() {
            _selectedIndex = index;
          });
        },
        items: const <BottomNavigationBarItem>[
          BottomNavigationBarItem(icon: Icon(Icons.schedule), label: 'Schedule'),
          BottomNavigationBarItem(icon: Icon(Icons.check), label: 'checkin'),
          BottomNavigationBarItem(icon: Icon(Icons.person), label: 'Staff Information'),
        ],
      ),
    );
  }
}
