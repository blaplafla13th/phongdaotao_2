from datetime import datetime

import numpy
import pandas as pd
from psycopg2.extensions import register_adapter, AsIs
from sqlalchemy import create_engine, Column, Integer, String, DateTime, func
from sqlalchemy.orm import declarative_base
from sqlalchemy.orm import sessionmaker


def addapt_numpy_float64(numpy_float64):
    return AsIs(numpy_float64)


def addapt_numpy_int64(numpy_int64):
    return AsIs(numpy_int64)


register_adapter(numpy.float64, addapt_numpy_float64)
register_adapter(numpy.int64, addapt_numpy_int64)

import logging

logging.basicConfig()
logging.getLogger('sqlalchemy').setLevel(logging.ERROR)

start = datetime.now()
db_config = {
    'NAME': 'room-test-db',
    'USER': 'user',
    'PASSWORD': 'password',
    'HOST': 'room-test-db',
    'PORT': '5432',
}
db_url = f"postgresql+psycopg2://{db_config['USER']}:{db_config['PASSWORD']}@{db_config['HOST']}:{db_config['PORT']}/{db_config['NAME']}"
engine = create_engine(db_url, echo=True)
Base = declarative_base()


class RoomDetail(Base):
    __tablename__ = 'room_details'
    id = Column(Integer, primary_key=True)
    name = Column(String)


class RoomDetailHistory(Base):
    __tablename__ = 'room_detail_histories'
    id = Column(Integer, primary_key=True)
    room_detail_id = Column(Integer)
    name = Column(String)
    created_at = Column(DateTime, nullable=True, default=func.now())
    status = Column(Integer)
    created_by = Column(Integer)


class RoomTest(Base):
    __tablename__ = 'room_tests'
    id = Column(Integer, primary_key=True)
    room_detail_id = Column(Integer)
    quantity = Column(Integer)
    shift_id = Column(Integer)
    need_supervisor = Column(Integer, default=2)
    last_edited = Column(Integer)


class shift(Base):
    __tablename__ = 'shifts'
    id = Column(Integer, primary_key=True)
    shift_start_time = Column(DateTime)


map_room_id = {}
map_shift_id = {}
Session = sessionmaker(bind=engine)
session = Session()
userid = open('userid', 'r').read()


def get_or_create_room(map_room_id, session, df_copy1, i):
    if df_copy1.iloc[i]['Phòng'] not in map_room_id:
        room = session.query(RoomDetail).filter(RoomDetail.name == df_copy1.iloc[i]['Phòng']).first()
        if room is None:
            room = RoomDetail(name=df_copy1.iloc[i]['Phòng'])
            session.add(room)
            session.commit()
            room_history = RoomDetailHistory(name=df_copy1.iloc[i]['Phòng'], status=0, created_by=userid)
        map_room_id[df_copy1.iloc[i]['Phòng']] = room.id
    return map_room_id[df_copy1.iloc[i]['Phòng']]


def get_or_create_shift(map_shift_id, session, df_copy1, i):
    if df_copy1.iloc[i]['ngaygio'] not in map_shift_id:
        shift_mini = session.query(shift).filter(shift.shift_start_time == df_copy1.iloc[i]['ngaygio']).first()
        if shift_mini is None:
            shift_mini = shift(shift_start_time=df_copy1.iloc[i]['ngaygio'])
            session.add(shift_mini)
            session.commit()
        map_shift_id[df_copy1.iloc[i]['ngaygio']] = shift_mini.id
    return map_shift_id[df_copy1.iloc[i]['ngaygio']]


try:
    df = pd.read_csv('data.csv')
    df.dropna(subset=['STT', 'Phòng'], inplace=True)
    df['ngaygio'] = pd.to_datetime(df['Ngày thi'] + ' ' + df['Giờ thi'], dayfirst=True)
    df_copy1 = df.copy()
    df_copy1 = df_copy1.drop(['Ngày thi', 'Giờ thi'], axis=1)
    df_copy2 = df.copy()
    df_copy2.drop_duplicates(subset=['Phòng'], inplace=True)
    df_copy3 = df.copy()
    df_copy3.drop_duplicates(subset=['ngaygio', 'Ca'], keep='first', inplace=True)
    df_copy3[['ngaygio', 'Ca']]

    for i in range(len(df_copy2)):
        get_or_create_room(map_room_id, session, df_copy2, i)

    for i in range(len(df_copy3)):
        get_or_create_shift(map_shift_id, session, df_copy3, i)

    for i in range(len(df_copy1)):
        room_id = get_or_create_room(map_room_id, session, df_copy1, i)
        ca_thi_id = get_or_create_shift(map_shift_id, session, df_copy1, i)
        room_test = RoomTest(room_detail_id=room_id, quantity=df_copy1.iloc[i]['SL'], shift_id=ca_thi_id,
                             last_edited=userid)
        session.add(room_test)
    session.commit()
    end = datetime.now()
    print("Running time: ", end - start)
except Exception:
    session.rollback()
