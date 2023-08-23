import 'package:flutter_application_staff/api/APISchedule.dart';
import 'package:flutter_application_staff/api/apicheckin.dart';
import 'package:flutter_application_staff/api/ApiChangePassWord.dart';
class Staff {
  static String? accessToken;
  int? id;
  String? name;
  String? email;
  String? phone;
  String? department_id;
  Staff({
      required this.id,
      this.name,
      this.email,
      this.phone,
      this.department_id

      });


  factory Staff.fromJson(Map<String, dynamic> json) {
    return Staff(
        id: json['id'],
        name: json['name'],
        email: json['email'],
        phone: json['phone'],
        department_id: json['department']
        )
    ;
  }
  Future<List> getSchedule() async{
    APISchedule apiSchedule = APISchedule();
    List<Map> list = await apiSchedule.getSchedule(this.id.toString());
    return list;
  }
  Future<List> checkin(String url) async{
    ApiCheckin apicheckin = ApiCheckin();
    List<String> list = await apicheckin.checkIn(url);
    return list;
  }
  Future<int> changePassWord(String oldPassWord, String newPassWord) async{
    return await ApiChangePassWord().changePassWord(oldPassWord, newPassWord);
  }


  @override
  String toString() {
    // TODO: implement toString
    return 'id: $id, name: $name, email: $email, phone: $phone';
  }


  
}