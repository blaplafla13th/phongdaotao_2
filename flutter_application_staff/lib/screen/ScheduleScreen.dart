import 'package:flutter/material.dart';
import 'package:flutter_application_staff/models/Staff.dart';

class ScheduleScreen extends StatefulWidget {
  Staff staff;
  ScheduleScreen({Key? key, required this.staff}) : super(key: key);

  @override
  State<ScheduleScreen> createState() => _ScheduleScreenState();
}

class _ScheduleScreenState extends State<ScheduleScreen> {
  Widget buildCell(
      int inDex, String timeSchedule,  BuildContext bld) {
    List<String> datetime = timeSchedule.replaceAll('Z', '').split(' ');
    List<String> dateOrigin = datetime[0].split('-');
    String date = '${dateOrigin[2]}-${dateOrigin[1]}-${dateOrigin[0]}';
    String time = datetime[1].substring(0, 5);

    return InkWell(
      onTap:(){
        print('tap');
      },
      child: Container(

        height: 45,
        margin: EdgeInsets.only(top: 15, left: 20, right: 20),
        padding: EdgeInsets.only(left: 5, right: 5),
        decoration: BoxDecoration(
            borderRadius: BorderRadius.circular(10),
            border: Border.all(color: Colors.blueAccent, width: 2),
            boxShadow: [
              BoxShadow(
                color: Colors.grey.withOpacity(0.5), // Màu đổ bóng
                spreadRadius: 5, // Độ rộng đổ bóng
                blurRadius: 7, // Độ mờ đổ bóng
                offset: Offset(0, 3), // Vị trí đổ bóng (ngang, dọc)
              )
            ]),
        child: Row(
          mainAxisAlignment: MainAxisAlignment.spaceBetween,
          children: [
            Text('${inDex + 1}'),
            Text('$date'),
            Text('${time}'),
            IconButton(
                onPressed: () {
                  showModalBottomSheet(
                      isScrollControlled: true,
                      context: bld,
                      builder: (BuildContext context) {
                        return CustomButtomSheet();
                      });
                },
                icon: Icon(
                  Icons.cancel,
                  color: Colors.red,
                ))
          ],
        ),
      ),
    );
  }

  Widget buildSchedule(List<dynamic> schedule, BuildContext bld) {
    return ListView.builder(
      itemCount: schedule.length,
      itemBuilder: (context, index) {
        return buildCell(index, schedule[index]['shift_start_time'], bld);
      },
    );
  }

  @override
  Widget build(BuildContext context) {
    // TODO: implement build
    return Scaffold(
        appBar: AppBar(
          title: Text('Schedule'),
          automaticallyImplyLeading: false,
        ),
        body: FutureBuilder(future: widget.staff.getSchedule(), builder: (context, snapshot){
          if(snapshot.hasData){
            return buildSchedule(snapshot.data as List<dynamic>, context);
          }
          else if(snapshot.hasError){
            return Center(child: Text('Error'));
          }
          else{
            return Center(child: CircularProgressIndicator());
          }
        }));
  }
}

class CustomButtomSheet extends StatelessWidget {
  @override
  Widget build(BuildContext context) {
    // TODO: implement build
    return Container(
      height: 650,
      child: Column(children: <Widget>[
        Center(
          child: Text(
            'Báo nghỉ',
            style: TextStyle(fontSize: 20),
          ),
        ),
        Row(
          mainAxisAlignment: MainAxisAlignment.start,
          children: [
            Flexible(
              child: Container(
                margin: EdgeInsets.only(top: 20, left: 20, right: 20),
                child: TextField(
                  textInputAction: TextInputAction.next,
                  decoration: InputDecoration(
                    border: OutlineInputBorder(
                      borderRadius: BorderRadius.all(Radius.circular(20)),
                    ),
                    labelText: 'Lý do',
                  ),
                ),
              ),
            ),
          ],
        ),
        Flexible(
            child: Container(
          margin: EdgeInsets.only(top: 20, left: 20, right: 20),
          child: TextField(
            textInputAction: TextInputAction.next,
            decoration: InputDecoration(
              border: OutlineInputBorder(
                borderRadius: BorderRadius.all(Radius.circular(20)),
              ),
              labelText: 'Số điện thoại cán bộ thay thế ',
            ),
          ),
        )),
        Flexible(
            child: Container(
          margin: EdgeInsets.only(top: 20, left: 20, right: 20),
          child: TextField(
            decoration: InputDecoration(
              border: OutlineInputBorder(
                borderRadius: BorderRadius.all(Radius.circular(20)),
              ),
              labelText: 'Họ tên cán bộ thay thế ',
            ),
          ),
        )),
        SizedBox(height: 30,),
       Flexible(child: Row(
         mainAxisAlignment: MainAxisAlignment.end,
         children: [
           ElevatedButton(onPressed: (){
              Navigator.pop(context);
           }, child: Text('Cancel', style: TextStyle(color: Colors.black),),
             style: ButtonStyle(
                backgroundColor: MaterialStateProperty.all<Color>(Colors.white),
             ),

           ),
           SizedBox(width: 20,),
           ElevatedButton(onPressed: (){}, child: Text('Submit')),
           SizedBox(width: 20,),
         ],
       ))
      ]),
    );
  }
}
