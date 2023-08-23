import 'dart:convert';
import 'package:http/http.dart' as http;
import 'package:flutter_application_staff/models/Staff.dart';
import 'dart:io';

class APISchedule {
  Future<List<Map>> getSchedule( String user_id, ) async {
    final queryParameters = {

      'user_id': user_id,
    };
    var uri = Uri.http('supervisor.blaplafla.me', '/api/assignments', queryParameters);
    final response = await http.get(uri,headers: {
      HttpHeaders.acceptHeader : '*/*',
    });
    if (response.statusCode==200){
      var bodyresponse = json.decode(response.body);
      List<Map> list = [];
      var data = bodyresponse['data'];
      for (var i in data){
        list.add(i);
      }
      return list;
    }
    else{
      return [];
    }
  }
}
