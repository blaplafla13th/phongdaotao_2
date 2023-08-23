import 'dart:convert';
import 'dart:io';
import 'package:http/http.dart' as http;
import 'package:flutter_application_staff/models/Staff.dart';


class ApiCheckin{
  Future<List<String>> checkIn (String url) async {
    var uri = Uri.parse(url);
    List<String> list1 =[];
    final response = await http.get(uri,headers: {
      HttpHeaders.authorizationHeader : 'Bearer ${Staff.accessToken}',
      HttpHeaders.acceptHeader: '*/*',
    });
    if (response.statusCode == 200){
      var data = json.decode(response.body);
      String position = 'Phòng thi'+ data['position'];
      String? examtestid = data['exam_test_id'] == null ? 'Không khả dụng': data['exam_test_id'].toString();
      String supervisor ='Cán bộ coi thi số:' + data['supervisor'].toString();
      list1.add(position);
      list1.add('Mã đề thi: '+ examtestid);
      list1.add(supervisor);

    }
    else{
      list1.add('Error, Status code');
      list1.add('Error. ${response.statusCode}');
      list1.add(' ');

    }
    return list1;
  }
}